let carrello = {};

let filtriAllergeni = [];

let categoriaAttiva = 'all';

document.addEventListener('DOMContentLoaded', function () {

    sincronizzaCarrello();

    renderProdotti();

    setInterval(verificaSessione, 5000);

    document.getElementById('btn-invia-ordine').addEventListener('click', () => {

        new bootstrap.Modal(document.getElementById('modalConfermaOrdine')).show();
    });

    document.getElementById('confirm-send-btn').addEventListener('click', inviaOrdine);
});

function sincronizzaCarrello() {

    fetch('../api/tavolo/tavolo_api.php?action=get_carrello')

        .then(r => r.json())

        .then(data => {

            carrello = {};

            data.forEach(item => {

                carrello[item.id_alimento] = {
                    nome: item.nome_piatto, 
                    qta: parseInt(item.quantita), 
                    prezzo: parseFloat(item.prezzo) 
                };
            });

            aggiornaUI();
        });
}

function renderProdotti() {

    const search = document.getElementById('search-bar').value.toLowerCase();

    document.querySelectorAll('.item-prodotto').forEach(item => {

        const card = item.querySelector('.card-prodotto');

        const nome = card.dataset.nome.toLowerCase();

        const desc = card.dataset.desc.toLowerCase();

        const cat = item.dataset.cat;

        const allergeniPiatto = card.dataset.allergeni.split(',').map(a => a.trim().toLowerCase());

        const matchSearch = nome.includes(search) || desc.includes(search);

        const matchCat = categoriaAttiva === 'all' || cat == categoriaAttiva;

        const matchAllergeni = filtriAllergeni.length === 0 || !filtriAllergeni.some(f => allergeniPiatto.includes(f.toLowerCase()));

        item.style.display = (matchSearch && matchCat && matchAllergeni) ? '' : 'none';
    });
}

function filtraCategoria(catId, btn) {

    categoriaAttiva = catId;

    document.querySelectorAll('.btn-categoria, .mobile-cat-btn').forEach(b => b.classList.remove('active'));

    btn.classList.add('active');

    renderProdotti();
}

function applicaFiltriAllergeni() {

    filtriAllergeni = [];

    document.querySelectorAll('#lista-allergeni-filtro input[type="checkbox"]:checked').forEach(cb => {

        filtriAllergeni.push(cb.value);
    });

    renderProdotti();
}

function resettaFiltriAllergeni() {

    document.querySelectorAll('#lista-allergeni-filtro input[type="checkbox"]').forEach(cb => cb.checked = false);

    filtriAllergeni = [];

    renderProdotti();
}

function syncCartBackend(id, delta) {

    const endpoint = delta > 0 ? 'aggiungi_al_carrello' : 'rimuovi_dal_carrello';

    const fd = new FormData();
    fd.append('id_alimento', id);

    if (delta > 0) fd.append('quantita', delta);

    fetch('../api/tavolo/tavolo_api.php?action=' + endpoint, { method: 'POST', body: fd });
}

function btnCardQty(event, id, delta, prezzo, nome) {

    event.stopPropagation();

    if (!carrello[id]) carrello[id] = { nome, qta: 0, prezzo };

    carrello[id].qta = Math.max(0, carrello[id].qta + delta);

    if (carrello[id].qta <= 0) delete carrello[id];

    syncCartBackend(id, delta);

    aggiornaUI();
}

let zoomState = { id: 0, prezzo: 0, qta: 1, nome: '', note: '' };

function apriZoom(event, card) {

    if (event.target.closest('.btn-card-qty')) return;

    zoomState = {
        id: parseInt(card.dataset.id),
        prezzo: parseFloat(card.dataset.prezzo),
        qta: 1, 
        nome: card.dataset.nome,
        note: ''
    };

    document.getElementById('zoom-img').src = card.dataset.img;
    document.getElementById('zoom-nome').textContent = card.dataset.nome;
    document.getElementById('zoom-desc').textContent = card.dataset.desc;
    document.getElementById('zoom-prezzo-unitario').textContent = card.dataset.prezzo;
    document.getElementById('zoom-note').value = '';

    const allergeni = card.dataset.allergeni.split(',').filter(a => a.trim());

    document.getElementById('zoom-allergeni').innerHTML = allergeni.length
        ? allergeni.map(a => `<span class="badge-alg">${a.trim()}</span>`).join('')
        : '<small class="text-muted">Nessun allergene dichiarato</small>';

    aggiornaZoomUI();

    new bootstrap.Modal(document.getElementById('modalZoom')).show();
}

function updateZoomQty(delta) {

    zoomState.qta = Math.max(1, zoomState.qta + delta);

    aggiornaZoomUI();
}

function aggiornaZoomUI() {

    document.getElementById('zoom-qty-display').textContent = zoomState.qta;

    document.getElementById('zoom-btn-totale').textContent = (zoomState.prezzo * zoomState.qta).toFixed(2) + '€';
}

function confermaZoom() {

    if (!carrello[zoomState.id]) carrello[zoomState.id] = { nome: zoomState.nome, qta: 0, prezzo: zoomState.prezzo };

    carrello[zoomState.id].qta += zoomState.qta;

    syncCartBackend(zoomState.id, zoomState.qta);

    aggiornaUI();

    bootstrap.Modal.getInstance(document.getElementById('modalZoom')).hide();

    mostraToast(`${zoomState.nome} aggiunto!`);
}

function aggiornaUI() {

    let totale = 0, pezzi = 0;

    for (const id in carrello) {

        const item = carrello[id];

        totale += item.qta * item.prezzo;

        pezzi += item.qta;

        const el = document.getElementById('q-' + id);
        if (el) el.textContent = item.qta;
    }

    document.querySelectorAll('[id^="q-"]').forEach(el => {
        const id = el.id.replace('q-', '');
        if (!carrello[id]) el.textContent = '0';
    });

    document.getElementById('soldi-header').textContent = totale.toFixed(2);
    document.getElementById('pezzi-header').textContent = pezzi;
}

function aggiornaModale() {

    const body = document.getElementById('corpo-carrello');

    const keys = Object.keys(carrello).filter(id => carrello[id].qta > 0);
    let totale = 0;

    if (!keys.length) {

        body.innerHTML = `<div class="text-center py-5 text-muted">
            <i class="fas fa-shopping-bag fa-3x mb-3" style="opacity:.3"></i>
            <h5>Il carrello è vuoto</h5><p class="small">Aggiungi piatti per iniziare</p></div>`;

        document.getElementById('totale-modale').textContent = '0.00';

        document.getElementById('btn-invia-ordine').disabled = true;

        return;
    }

    body.innerHTML = '<ul class="list-group list-group-flush px-3">' + keys.map(id => {

        const item = carrello[id];

        const sub = (item.qta * item.prezzo).toFixed(2);

        totale += item.qta * item.prezzo;

        return `<li class="list-group-item d-flex justify-content-between align-items-center px-2 py-3">
            <div>
                <strong>${item.nome}</strong><br>
                <small class="text-muted">${item.prezzo.toFixed(2)}€ cad.</small>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="qty-capsule" style="width:120px;">
                    <button class="btn-circle btn-minus" onclick="modificaQtaModale(${id}, -1)"><i class="fas fa-minus"></i></button>
                    <span class="qty-input">${item.qta}</span>
                    <button class="btn-circle btn-plus" onclick="modificaQtaModale(${id}, 1)"><i class="fas fa-plus"></i></button>
                </div>
                <strong class="text-price">${sub}€</strong>
            </div>
        </li>`;
    }).join('') + '</ul>';

    document.getElementById('totale-modale').textContent = totale.toFixed(2);

    document.getElementById('btn-invia-ordine').disabled = false;
}

function modificaQtaModale(id, delta) {
    if (!carrello[id]) return;

    carrello[id].qta = Math.max(0, carrello[id].qta + delta);
    if (carrello[id].qta <= 0) delete carrello[id];

    syncCartBackend(id, delta);

    aggiornaUI();
    aggiornaModale();
}

function inviaOrdine() {

    const prodotti = Object.keys(carrello).map(id => ({
        id: parseInt(id),
        qta: carrello[id].qta,
        note: '' 
    })).filter(p => p.qta > 0);

    if (!prodotti.length) return;

    bootstrap.Modal.getInstance(document.getElementById('modalConfermaOrdine')).hide();

    fetch('../api/tavolo/tavolo_api.php?action=invia_ordine', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ prodotti })
    })
        .then(r => r.json())
        .then(data => {

            if (data.success) {

                carrello = {};

                aggiornaUI();

                bootstrap.Modal.getInstance(document.getElementById('modalCarrello'))?.hide();

                new bootstrap.Modal(document.getElementById('modalSuccesso')).show();
            } else {

                mostraToast(data.message || 'Errore', true);
            }
        });
}

function apriStorico() {

    fetch('../api/tavolo/tavolo_api.php?action=leggi_ordini_tavolo')
        .then(r => r.json())
        .then(data => {

            const body = document.getElementById('corpo-ordini');

            let totaleSommato = 0;

            if (!data.length) {

                body.innerHTML = `<div class="text-center text-muted py-5">
                    <i class="fas fa-receipt fa-3x mb-3 opacity-25"></i>
                    <h5>Nessun ordine</h5><p class="small">Non hai ancora inviato ordini.</p></div>`;
            } else {

                body.innerHTML = data.map(o => {

                    totaleSommato += parseFloat(o.totale);

                    const badgeClass = o.stato === 'in_attesa' ? 'bg-warning text-dark' : o.stato === 'in_preparazione' ? 'bg-info text-white' : 'bg-success';

                    const labels = { in_attesa: 'In attesa', in_preparazione: 'In preparazione', pronto: 'Pronto' };

                    return `<div class="border rounded-4 p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge ${badgeClass} rounded-pill px-3 py-2">${labels[o.stato] || o.stato}</span>
                            <small class="text-muted"><i class="fas fa-clock me-1"></i>${o.ora} • ${o.data}</small>
                        </div>
                        ${o.piatti.map(p => `<div class="d-flex justify-content-between small py-1 border-bottom">
                            <span>${p.qta}x <strong>${p.nome}</strong></span>
                            <span class="text-muted">${p.prezzo}€</span>
                        </div>`).join('')}
                        <div class="text-end mt-2 fw-bold text-price">${o.totale}€</div>
                    </div>`;
                }).join('');
            }

            document.getElementById('totale-storico').textContent = totaleSommato.toFixed(2);

            new bootstrap.Modal(document.getElementById('modalOrdini')).show();
        });
}

function verificaSessione() {

    fetch('../api/tavolo/tavolo_api.php?action=verifica_sessione')
        .then(r => r.json())
        .then(data => {

            if (!data.valida) {

                alert('La sessione è stata terminata dal gestore.');

                window.location.href = '../logout.php';
            }
        })
        .catch(() => { }); 
}

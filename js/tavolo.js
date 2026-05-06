let filtriAllergeni = [];
let categoriaAttiva = 'all';

document.addEventListener('DOMContentLoaded', function () {
    renderProdotti();
    setInterval(function () {
        if (!document.querySelector('.modal.show')) location.reload();
    }, 30000);
});

function renderProdotti() {
    const search = document.getElementById('search-bar').value.toLowerCase();
    document.querySelectorAll('.item-prodotto').forEach(item => {
        const card            = item.querySelector('.card-prodotto');
        const nome            = card.dataset.nome.toLowerCase();
        const desc            = card.dataset.desc.toLowerCase();
        const allergeniPiatto = card.dataset.allergeni.split(',').map(a => a.trim().toLowerCase());
        const matchSearch     = nome.includes(search) || desc.includes(search);
        const matchCat        = categoriaAttiva === 'all' || item.dataset.cat == categoriaAttiva;
        const matchAllergeni  = filtriAllergeni.length === 0 ||
                                !filtriAllergeni.some(f => allergeniPiatto.includes(f.toLowerCase()));
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

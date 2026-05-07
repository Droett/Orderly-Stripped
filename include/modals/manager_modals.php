<div class="modal fade" id="modalAggiungiTavolo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom shadow-lg">
            <div class="modal-header border-0 p-4 pb-2">
                <div>
                    <h3 class="modal-title fw-bold">Nuovo Tavolo 🪑</h3>
                    <p class="m-0 text-muted">Crea un nuovo tavolo per il ristorante</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <form id="form-aggiungi-tavolo" method="POST" action="manager.php?action=aggiungi_tavolo">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="small text-muted fw-bold mb-1">Nome Tavolo</label>
                            <input type="text" id="nuovo_nome_tavolo" name="nome_tavolo" class="form-control" placeholder="Es: Tavolo 1" required>
                        </div>
                        <div class="col-6">
                            <label class="small text-muted fw-bold mb-1">Password</label>
                            <input type="text" id="nuovo_password_tavolo" name="password" class="form-control" placeholder="Es: 1234" required>
                        </div>
                        <div class="col-6">
                            <label class="small text-muted fw-bold mb-1">Posti</label>
                            <input type="number" id="nuovo_posti_tavolo" name="posti" class="form-control" value="4" min="1" max="20">
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer border-0 p-4 bg-light-custom">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Annulla</button>
                <button type="submit" form="form-aggiungi-tavolo" class="btn btn-dark rounded-pill px-5 fw-bold">
                    <i class="fas fa-plus me-2"></i>Registra Tavolo
                </button>
            </div>
        </div>
    </div>
</div>


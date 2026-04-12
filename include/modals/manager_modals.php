<!-- Modale: Aggiungi Tavolo -->
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
                <div class="row g-3">
                    <div class="col-12">
                        <label class="small text-muted fw-bold mb-1">Nome Tavolo</label>
                        <input type="text" id="nuovo_nome_tavolo" class="form-control" placeholder="Es: Tavolo 1">
                    </div>
                    <div class="col-6">
                        <label class="small text-muted fw-bold mb-1">Password</label>
                        <input type="text" id="nuovo_password_tavolo" class="form-control" placeholder="Es: 1234">
                    </div>
                    <div class="col-6">
                        <label class="small text-muted fw-bold mb-1">Posti</label>
                        <input type="number" id="nuovo_posti_tavolo" class="form-control" value="4" min="1" max="20">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 bg-light-custom">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold"
                    data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-dark rounded-pill px-5 fw-bold" onclick="aggiungiTavolo()">
                    <i class="fas fa-plus me-2"></i>Registra Tavolo
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modale: Modifica Tavolo -->
<div class="modal fade" id="modalModificaTavolo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom shadow-lg">
            <div class="modal-header border-0 p-4 pb-2">
                <h3 class="modal-title fw-bold">Modifica Tavolo ✏️</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="mod_id_tavolo">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="small text-muted fw-bold mb-1">Nome</label>
                        <input type="text" id="mod_nome_tavolo" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="small text-muted fw-bold mb-1">Password</label>
                        <input type="text" id="mod_password" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="small text-muted fw-bold mb-1">Posti</label>
                        <input type="number" id="mod_posti" class="form-control" min="1" max="20">
                    </div>
                    <div class="col-12">
                        <label class="small text-muted fw-bold mb-1">Stato</label>
                        <select id="mod_stato" class="form-select">
                            <option value="libero">🟢 Libero</option>
                            <option value="riservato">🟡 Riservato</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold"
                    data-bs-dismiss="modal">Chiudi</button>
                <button type="button" class="btn btn-dark rounded-pill px-5 fw-bold" onclick="modificaTavolo()">Salva
                    Modifiche</button>
            </div>
        </div>
    </div>
</div>

<!-- Modale: Modifica Piatto -->
<div class="modal fade" id="modalModifica" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Modifica Piatto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="../api/manager/manager_api.php?action=modifica_piatto" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_alimento" id="mod_id">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="small text-muted">Nome</label>
                            <input type="text" name="nome_piatto" id="mod_nome" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted">Prezzo (€)</label>
                            <input type="number" step="0.01" name="prezzo" id="mod_prezzo" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="small text-muted">Categoria</label>
                            <select name="id_categoria" id="mod_cat" class="form-select" required>
                                <?php foreach ($categorie_array as $cat): ?>
                                    <option value="<?php echo $cat['id_categoria']; ?>"><?php echo $cat['nome_categoria']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="small text-muted">Descrizione</label>
                            <textarea name="descrizione" id="mod_desc" class="form-control" rows="3"
                                style="resize: none;"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="small text-muted fw-bold mb-2">ALLERGENI</label>
                            <div class="d-flex flex-wrap gap-2 p-3 rounded bg-light-custom">
                                <?php foreach ($ALLERGENI as $a): ?>
                                    <div class="form-check form-check-inline m-0 me-3">
                                        <input class="form-check-input mod-allergeni" type="checkbox" name="allergeni[]" value="<?php echo $a; ?>" id="mod_al_<?php echo $a; ?>">
                                        <label class="form-check-label small" for="mod_al_<?php echo $a; ?>"><?php echo $a; ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex align-items-center gap-3">
                                <img id="preview_img" src=""
                                    style="width: 80px; height: 80px; object-fit: cover; border-radius: 12px; border: 1px solid #ddd;">
                                <div class="w-100">
                                    <label class="small text-muted">Sostituisci Immagine</label>
                                    <input type="file" name="immagine" class="form-control" accept="image/*">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-0 mt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-primary fw-bold px-4">Salva Modifiche</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast-container position-fixed bottom-0 start-50 translate-middle-x p-3">
    <div id="managerToast" class="toast align-items-center text-white bg-success border-0 shadow-lg" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-bold">
                <i class="fas fa-check-circle me-2"></i> <span id="toast-msg-manager">Azione eseguita!</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
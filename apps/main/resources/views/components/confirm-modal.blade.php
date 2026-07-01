{{--
    Reusable confirmation modal.
    Trigger via JS: window.confirmModal('Pesan?', callbackFn, 'Label Tombol OK')
    For forms:      onclick="confirmModal('Msg?', () => this.closest('form').submit())"
--}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:380px">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center px-4 pt-4 pb-2">
                <div class="mb-3">
                    <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size:2.25rem"></i>
                </div>
                <p class="fw-semibold mb-0" id="confirmModalMessage" style="font-size:.95rem">
                    Apakah Anda yakin?
                </p>
            </div>
            <div class="modal-footer border-0 justify-content-center gap-2 pb-4">
                <button type="button"
                        class="btn btn-outline-secondary px-4"
                        data-bs-dismiss="modal">Batal</button>
                <button type="button"
                        class="btn btn-danger px-4"
                        id="confirmModalOk">Ya, Lanjutkan</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var _cb = null;
    window.confirmModal = function (message, callback, okLabel) {
        document.getElementById('confirmModalMessage').textContent = message || 'Apakah Anda yakin?';
        document.getElementById('confirmModalOk').textContent = okLabel || 'Ya, Lanjutkan';
        _cb = callback || null;
        new bootstrap.Modal(document.getElementById('confirmModal')).show();
    };
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('confirmModalOk').addEventListener('click', function () {
            bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
            if (typeof _cb === 'function') _cb();
        });
    });
}());
</script>

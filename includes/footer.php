
    </div><!-- /page-body -->
</div><!-- /main-content -->

<!-- Modal: Alterar Senha -->
<div class="modal fade" id="modalAlterarSenha" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;border:none;">
            <div class="modal-body p-4">
                <h5 class="mb-4 d-flex align-items-center gap-2" style="font-family:'Sora',sans-serif;font-weight:700;">
                    <i class="bi bi-shield-lock" style="color:#1a56a0;"></i> Alterar Senha
                </h5>

                <div id="alterarSenhaAlerta" class="alert d-none align-items-center gap-2 mb-3" style="border-radius:8px;font-size:.88rem;"></div>

                <form id="formAlterarSenha" novalidate>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight:500;font-size:.88rem;">Senha atual <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" id="senhaAtualInput" class="form-control" style="border-radius:8px 0 0 8px;border-color:#d1dff0;" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="alternarVisibilidadeSenha('senhaAtualInput', this)" style="border-color:#d1dff0;border-radius:0 8px 8px 0;">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:500;font-size:.88rem;">Nova senha <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" id="novaSenhaInput" class="form-control" minlength="8" style="border-radius:8px 0 0 8px;border-color:#d1dff0;" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="alternarVisibilidadeSenha('novaSenhaInput', this)" style="border-color:#d1dff0;border-radius:0 8px 8px 0;">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">Mínimo de 8 caracteres.</small>
                    </div>

                    <div class="mb-1">
                        <label class="form-label" style="font-weight:500;font-size:.88rem;">Confirmar nova senha <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" id="confirmarNovaSenhaInput" class="form-control" style="border-radius:8px 0 0 8px;border-color:#d1dff0;" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="alternarVisibilidadeSenha('confirmarNovaSenhaInput', this)" style="border-color:#d1dff0;border-radius:0 8px 8px 0;">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-outline-secondary px-4" style="border-radius:8px;" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnSalvarSenha" class="btn btn-success px-4" style="border-radius:8px;font-weight:600;" onclick="enviarAlterarSenha()">
                    <i class="bi bi-floppy me-2"></i>Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function alternarVisibilidadeSenha(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    input.type = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}

function mostrarAlertaSenha(mensagem, tipo) {
    const alerta = document.getElementById('alterarSenhaAlerta');
    alerta.className = 'alert alert-' + tipo + ' d-flex align-items-center gap-2 mb-3';
    alerta.style.borderRadius = '8px';
    alerta.style.fontSize = '.88rem';
    alerta.textContent = mensagem;
}

function enviarAlterarSenha() {
    const form = document.getElementById('formAlterarSenha');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const senhaAtual = document.getElementById('senhaAtualInput').value;
    const novaSenha  = document.getElementById('novaSenhaInput').value;
    const confirmar  = document.getElementById('confirmarNovaSenhaInput').value;

    if (novaSenha.length < 8) {
        mostrarAlertaSenha('A nova senha deve ter no mínimo 8 caracteres.', 'danger');
        return;
    }
    if (novaSenha !== confirmar) {
        mostrarAlertaSenha('As senhas não conferem.', 'danger');
        return;
    }

    const btn = document.getElementById('btnSalvarSenha');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';

    const dados = new FormData();
    dados.append('senha_atual', senhaAtual);
    dados.append('nova_senha', novaSenha);
    dados.append('confirmar_senha', confirmar);

    fetch('<?= BASE_URL ?>/auth/alterar_senha.php', { method: 'POST', body: dados })
        .then(r => r.json())
        .then(resp => {
            if (resp.sucesso) {
                mostrarAlertaSenha(resp.mensagem, 'success');
                form.reset();
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modalAlterarSenha'))?.hide();
                }, 1400);
            } else {
                mostrarAlertaSenha(resp.mensagem, 'danger');
            }
        })
        .catch(() => {
            mostrarAlertaSenha('Erro de conexão. Tente novamente.', 'danger');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-floppy me-2"></i>Salvar';
        });
}

document.getElementById('modalAlterarSenha')?.addEventListener('hidden.bs.modal', function () {
    document.getElementById('formAlterarSenha').reset();
    const alerta = document.getElementById('alterarSenhaAlerta');
    alerta.className = 'alert d-none';
    alerta.textContent = '';
});
</script>
<?php if (!empty($scriptsExtras)) echo $scriptsExtras; ?>
</body>
</html>

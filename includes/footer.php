
    </div><!-- /page-body -->
</div><!-- /main-content -->

<!-- Modal: Alterar Senha -->
<div class="modal fade" id="modalAlterarSenha" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:8px;border:none;overflow:hidden;">
            <div class="modal-body p-4">
                <h5 class="mb-4 d-flex align-items-center gap-2" style="font-family:'Sora',sans-serif;font-weight:700;">
                    <i class="bi bi-shield-lock" style="color:#1a56a0;"></i> Alterar Senha
                </h5>

                <div id="alterarSenhaAlerta" class="alert d-none align-items-center gap-2 mb-3" style="border-radius:6px;font-size:.88rem;"></div>

                <form id="formAlterarSenha" novalidate>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight:500;font-size:.88rem;">Senha atual <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" id="senhaAtualInput" class="form-control" style="border-radius:6px 0 0 6px;border-color:#d1dff0;" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="alternarVisibilidadeSenha('senhaAtualInput', this)" style="border-color:#d1dff0;border-radius:0 6px 6px 0;">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:500;font-size:.88rem;">Nova senha <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" id="novaSenhaInput" class="form-control" minlength="8" style="border-radius:6px 0 0 6px;border-color:#d1dff0;" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="alternarVisibilidadeSenha('novaSenhaInput', this)" style="border-color:#d1dff0;border-radius:0 6px 6px 0;">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">Mínimo de 8 caracteres.</small>
                    </div>

                    <div class="mb-1">
                        <label class="form-label" style="font-weight:500;font-size:.88rem;">Confirmar nova senha <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" id="confirmarNovaSenhaInput" class="form-control" style="border-radius:6px 0 0 6px;border-color:#d1dff0;" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="alternarVisibilidadeSenha('confirmarNovaSenhaInput', this)" style="border-color:#d1dff0;border-radius:0 6px 6px 0;">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-outline-secondary px-4" style="border-radius:6px;" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnSalvarSenha" class="btn btn-success px-4" style="border-radius:6px;font-weight:600;" onclick="enviarAlterarSenha()">
                    <i class="bi bi-floppy me-2"></i>Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Aviso (substitui alert()/confirm() nativos do navegador) -->
<div class="modal fade" id="modalAviso" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:8px;border:none;overflow:hidden;">
            <div class="modal-body text-center p-5">
                <div id="avisoIconWrap" style="width:80px;height:80px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                    <i id="avisoIcon" class="bi" style="font-size:2.2rem;"></i>
                </div>
                <h5 id="avisoTitulo" style="font-family:'Sora',sans-serif;font-weight:700;font-size:1.35rem;margin-bottom:10px;"></h5>
                <p id="avisoMensagem" class="text-muted mb-4" style="font-size:1rem;"></p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" id="avisoBtnCancelar" class="btn btn-outline-secondary px-5 py-2" style="border-radius:6px;font-size:.95rem;display:none;" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="avisoBtnOk" class="btn btn-primary px-5 py-2" style="border-radius:6px;font-size:.95rem;" data-bs-dismiss="modal">OK</button>
                </div>
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

// ── Modal de aviso global (substitui alert()/confirm()) ─────
let avisoCallbackConfirmar = null;

function mostrarAviso(mensagem, tipo, titulo) {
    tipo = tipo || 'warning';
    const mapa = {
        warning: { cor: '#fd7e14', bg: '#fff8e6', icone: 'bi-exclamation-triangle-fill', titulo: 'Atenção' },
        danger:  { cor: '#dc3545', bg: '#fff2f2', icone: 'bi-exclamation-circle-fill',   titulo: 'Erro' },
        success: { cor: '#198754', bg: '#e9f7ef', icone: 'bi-check-circle-fill',         titulo: 'Sucesso' },
        info:    { cor: '#1a56a0', bg: '#e8f1fb', icone: 'bi-info-circle-fill',          titulo: 'Aviso' },
    };
    const cfg = mapa[tipo] || mapa.warning;

    document.getElementById('avisoIconWrap').style.background = cfg.bg;
    document.getElementById('avisoIcon').className = 'bi ' + cfg.icone;
    document.getElementById('avisoIcon').style.color = cfg.cor;
    document.getElementById('avisoTitulo').textContent = titulo || cfg.titulo;
    document.getElementById('avisoMensagem').innerHTML = mensagem;
    document.getElementById('avisoBtnCancelar').style.display = 'none';

    const btnOk = document.getElementById('avisoBtnOk');
    btnOk.textContent = 'OK';
    btnOk.className = 'btn btn-primary px-4';
    avisoCallbackConfirmar = null;

    new bootstrap.Modal(document.getElementById('modalAviso')).show();
}

function confirmarAviso(mensagem, onConfirmar, opcoes) {
    opcoes = opcoes || {};

    document.getElementById('avisoIconWrap').style.background = opcoes.bg || '#fff2f2';
    document.getElementById('avisoIcon').className = 'bi ' + (opcoes.icone || 'bi-trash');
    document.getElementById('avisoIcon').style.color = opcoes.cor || '#dc3545';
    document.getElementById('avisoTitulo').textContent = opcoes.titulo || 'Confirmar ação';
    document.getElementById('avisoMensagem').innerHTML = mensagem;
    document.getElementById('avisoBtnCancelar').style.display = 'inline-block';

    const btnOk = document.getElementById('avisoBtnOk');
    btnOk.textContent = opcoes.textoConfirmar || 'Confirmar';
    btnOk.className = 'btn px-4 ' + (opcoes.classeConfirmar || 'btn-danger');
    avisoCallbackConfirmar = onConfirmar;

    new bootstrap.Modal(document.getElementById('modalAviso')).show();
}

document.getElementById('avisoBtnOk').addEventListener('click', function () {
    if (avisoCallbackConfirmar) {
        const cb = avisoCallbackConfirmar;
        avisoCallbackConfirmar = null;
        cb();
    }
});
</script>
<?php if (!empty($scriptsExtras)) echo $scriptsExtras; ?>
</body>
</html>

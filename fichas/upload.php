<?php
$paginaTitulo = 'Upload de Fichas';
$paginaAtiva  = 'upload';
require_once __DIR__ . '/../includes/header.php';
Auth::exigirPerfil(['admin','administracao']);

$db = Database::get();

// Carrega pacientes e tipos para os selects
$pacientes = $db->query('SELECT id, nome FROM pacientes ORDER BY nome ASC')->fetchAll();
$tipos     = $db->query('SELECT id, nome, cor FROM tipos_ficha WHERE ativo = 1 ORDER BY nome ASC')->fetchAll();
?>

<!-- Cabeçalho -->
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="listar.php" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="mb-0" style="font-family:'Sora',sans-serif;font-weight:700;">Upload de Fichas</h4>
        <p class="text-muted mb-0" style="font-size:.85rem;">Envie um ou mais PDFs de uma só vez</p>
    </div>
</div>

<?php if (empty($pacientes)): ?>
<div class="alert alert-warning d-flex align-items-center gap-2" style="border-radius:10px;">
    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
    Nenhum paciente cadastrado. <a href="/SysFAA/pacientes/cadastrar.php" class="ms-1 fw-semibold">Cadastre um paciente</a> antes de fazer o upload.
</div>
<?php else: ?>

<div class="row g-4">

    <!-- Formulário -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-body p-4">
                <h6 class="mb-3" style="font-family:'Sora',sans-serif;font-weight:600;">Informações das fichas</h6>
                <p class="text-muted mb-4" style="font-size:.83rem;">
                    Todos os arquivos enviados de uma vez receberão as mesmas informações abaixo.
                </p>

                <form id="formUpload" novalidate>

                    <!-- Paciente -->
                    <div class="mb-3">
                        <label class="form-label" style="font-weight:500;font-size:.88rem;">
                            Paciente <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white" style="border-color:#d1dff0;border-radius:8px 0 0 8px;">
                                <i class="bi bi-person text-muted"></i>
                            </span>
                            <select id="paciente_id" name="paciente_id" class="form-select"
                                    style="border-color:#d1dff0;border-left:none;border-radius:0 8px 8px 0;" required>
                                <option value="">Selecione o paciente...</option>
                                <?php foreach ($pacientes as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Busca rápida de paciente -->
                        <input type="text" id="buscaPaciente" class="form-control form-control-sm mt-1"
                               placeholder="Filtrar paciente pelo nome..."
                               style="border-radius:6px;border-color:#d1dff0;font-size:.82rem;">
                    </div>

                    <!-- Tipo de ficha -->
                    <div class="mb-3">
                        <label class="form-label" style="font-weight:500;font-size:.88rem;">
                            Tipo de ficha <span class="text-danger">*</span>
                        </label>
                        <select id="tipo_ficha_id" name="tipo_ficha_id" class="form-select"
                                style="border-radius:8px;border-color:#d1dff0;" required>
                            <option value="">Selecione o tipo...</option>
                            <?php foreach ($tipos as $t): ?>
                            <option value="<?= $t['id'] ?>" data-cor="<?= $t['cor'] ?>">
                                <?= htmlspecialchars($t['nome']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Data da ficha -->
                    <div class="mb-4">
                        <label class="form-label" style="font-weight:500;font-size:.88rem;">
                            Data da ficha <span class="text-danger">*</span>
                        </label>
                        <input type="date" id="data_ficha" name="data_ficha"
                               class="form-control"
                               style="border-radius:8px;border-color:#d1dff0;"
                               value="<?= date('Y-m-d') ?>"
                               max="<?= date('Y-m-d') ?>"
                               required>
                    </div>

                    <!-- Área de drop -->
                    <div id="dropZone" class="drop-zone mb-3">
                        <input type="file" id="arquivos" name="arquivos[]"
                               accept="application/pdf" multiple style="display:none;">
                        <div class="drop-content" id="dropContent">
                            <i class="bi bi-cloud-upload drop-icon"></i>
                            <p class="drop-titulo">Arraste os PDFs aqui</p>
                            <p class="drop-sub">ou <button type="button" class="btn-link-drop" onclick="document.getElementById('arquivos').click()">clique para selecionar</button></p>
                            <p class="drop-info">Somente PDF · Máximo 20 MB por arquivo</p>
                        </div>
                    </div>

                    <!-- Lista de arquivos selecionados -->
                    <div id="listaArquivos" class="mb-3" style="display:none;"></div>

                    <button type="button" id="btnEnviar" class="btn btn-primary w-100 btn-lg"
                            style="border-radius:8px;font-family:'Sora',sans-serif;font-weight:600;" disabled>
                        <i class="bi bi-cloud-upload me-2"></i>Enviar Fichas
                    </button>

                </form>
            </div>
        </div>
    </div>

    <!-- Painel de progresso -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px;min-height:300px;">
            <div class="card-body p-4">
                <h6 class="mb-3" style="font-family:'Sora',sans-serif;font-weight:600;">
                    Progresso do envio
                </h6>

                <div id="estadoVazio" class="text-center text-muted py-5">
                    <i class="bi bi-inbox d-block mb-2" style="font-size:2.5rem;opacity:.4;"></i>
                    <p style="font-size:.88rem;">Selecione os arquivos e clique em "Enviar Fichas"</p>
                </div>

                <div id="painelProgresso" style="display:none;">
                    <!-- Barra geral -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1" style="font-size:.82rem;">
                            <span class="text-muted">Progresso geral</span>
                            <span id="txtProgresso" style="font-weight:600;color:#1a56a0;">0%</span>
                        </div>
                        <div class="progress" style="height:8px;border-radius:6px;background:#e8edf5;">
                            <div id="barraGeral" class="progress-bar"
                                 style="background:linear-gradient(90deg,#1a56a0,#2d6fc4);border-radius:6px;width:0%;transition:width .3s;">
                            </div>
                        </div>
                    </div>

                    <!-- Lista de itens individuais -->
                    <div id="itensProgresso" style="max-height:380px;overflow-y:auto;"></div>

                    <!-- Resumo final -->
                    <div id="resumoFinal" style="display:none;" class="mt-3 p-3"
                         style="border-radius:10px;">
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<?php endif; ?>

<style>
.drop-zone {
    border: 2px dashed #d1dff0;
    border-radius: 12px;
    background: #f8fafd;
    padding: 28px 20px;
    text-align: center;
    transition: border-color .2s, background .2s;
    cursor: pointer;
}
.drop-zone.drag-over {
    border-color: #2d6fc4;
    background: #e8f1fb;
}
.drop-zone.tem-arquivos {
    border-color: #198754;
    background: #f0fdf4;
}
.drop-icon { font-size: 2.2rem; color: #7a8aaa; display: block; margin-bottom: 8px; }
.drop-titulo { font-family: 'Sora', sans-serif; font-weight: 600; color: #1e2d45; margin: 0 0 4px; font-size: .95rem; }
.drop-sub { font-size: .85rem; color: #7a8aaa; margin: 0 0 4px; }
.drop-info { font-size: .78rem; color: #aab4c8; margin: 0; }
.btn-link-drop { background: none; border: none; color: #1a56a0; font-weight: 600; cursor: pointer; padding: 0; text-decoration: underline; }

.item-arquivo {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px; border-radius: 8px;
    background: #f8fafd; margin-bottom: 6px;
    font-size: .85rem;
}
.item-arquivo .nome { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #1e2d45; }
.item-arquivo .tamanho { color: #7a8aaa; font-size: .78rem; flex-shrink: 0; }
.item-arquivo .status { flex-shrink: 0; font-size: .8rem; }
.item-arquivo .barra-item { height: 3px; border-radius: 2px; background: #e8edf5; margin-top: 4px; }
.item-arquivo .barra-item-fill { height: 100%; border-radius: 2px; background: #1a56a0; width: 0%; transition: width .2s; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
const dropZone    = document.getElementById('dropZone');
const inputArq    = document.getElementById('arquivos');
const listaArq    = document.getElementById('listaArquivos');
const btnEnviar   = document.getElementById('btnEnviar');
const dropContent = document.getElementById('dropContent');

let arquivosSelecionados = [];

// ── Drag & drop ──────────────────────────────────────────────
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
dropZone.addEventListener('dragleave', ()  => dropZone.classList.remove('drag-over'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    processarArquivos([...e.dataTransfer.files]);
});
dropZone.addEventListener('click', e => {
    if (e.target !== document.querySelector('.btn-link-drop')) inputArq.click();
});
inputArq.addEventListener('change', () => processarArquivos([...inputArq.files]));

// ── Filtro de paciente ───────────────────────────────────────
document.getElementById('buscaPaciente').addEventListener('input', function () {
    const q   = this.value.toLowerCase();
    const sel = document.getElementById('paciente_id');
    [...sel.options].forEach(op => {
        if (op.value === '') return;
        op.style.display = op.text.toLowerCase().includes(q) ? '' : 'none';
    });
});

// ── Processar arquivos selecionados ─────────────────────────
function processarArquivos(files) {
    const novos = files.filter(f => {
        if (f.type !== 'application/pdf') { alert(`"${f.name}" não é um PDF e foi ignorado.`); return false; }
        if (f.size > 20 * 1024 * 1024)   { alert(`"${f.name}" excede 20 MB e foi ignorado.`);  return false; }
        return true;
    });

    arquivosSelecionados = [...arquivosSelecionados, ...novos];
    renderizarLista();
}

function renderizarLista() {
    if (arquivosSelecionados.length === 0) {
        listaArq.style.display = 'none';
        dropZone.classList.remove('tem-arquivos');
        btnEnviar.disabled = true;
        return;
    }

    dropZone.classList.add('tem-arquivos');
    dropContent.querySelector('.drop-icon').className = 'bi bi-check-circle-fill drop-icon';
    dropContent.querySelector('.drop-icon').style.color = '#198754';
    dropContent.querySelector('.drop-titulo').textContent =
        arquivosSelecionados.length + ' arquivo(s) selecionado(s)';

    listaArq.style.display = 'block';
    listaArq.innerHTML = arquivosSelecionados.map((f, i) => `
        <div class="item-arquivo" id="pre_${i}">
            <i class="bi bi-file-earmark-pdf text-danger flex-shrink-0"></i>
            <span class="nome" title="${escapar(f.name)}">${escapar(f.name)}</span>
            <span class="tamanho">${formatarTamanho(f.size)}</span>
            <button type="button" class="btn btn-sm p-0 text-muted" onclick="removerArquivo(${i})" title="Remover">
                <i class="bi bi-x-lg" style="font-size:.8rem;"></i>
            </button>
        </div>
    `).join('');

    btnEnviar.disabled = false;
}

function removerArquivo(i) {
    arquivosSelecionados.splice(i, 1);
    renderizarLista();
}

// ── Envio sequencial ─────────────────────────────────────────
document.getElementById('btnEnviar').addEventListener('click', async () => {
    const pacienteId  = document.getElementById('paciente_id').value;
    const tipoFichaId = document.getElementById('tipo_ficha_id').value;
    const dataFicha   = document.getElementById('data_ficha').value;

    if (!pacienteId)  { alert('Selecione um paciente.');      return; }
    if (!tipoFichaId) { alert('Selecione o tipo de ficha.');  return; }
    if (!dataFicha)   { alert('Informe a data da ficha.');    return; }

    btnEnviar.disabled = true;
    document.getElementById('estadoVazio').style.display  = 'none';
    document.getElementById('painelProgresso').style.display = 'block';
    document.getElementById('resumoFinal').style.display  = 'none';

    const total = arquivosSelecionados.length;
    let   ok = 0, falha = 0;

    // Monta itens de progresso
    const itens = document.getElementById('itensProgresso');
    itens.innerHTML = arquivosSelecionados.map((f, i) => `
        <div class="item-arquivo flex-wrap" id="prog_${i}">
            <i class="bi bi-file-earmark-pdf text-danger flex-shrink-0"></i>
            <span class="nome" title="${escapar(f.name)}">${escapar(f.name)}</span>
            <span class="tamanho">${formatarTamanho(f.size)}</span>
            <span class="status text-muted" id="status_${i}">Aguardando...</span>
            <div class="w-100"><div class="barra-item"><div class="barra-item-fill" id="barra_${i}"></div></div></div>
        </div>
    `).join('');

    for (let i = 0; i < total; i++) {
        const f = arquivosSelecionados[i];
        document.getElementById(`status_${i}`).innerHTML = '<span class="text-primary">Enviando...</span>';
        document.getElementById(`barra_${i}`).style.width = '40%';

        const fd = new FormData();
        fd.append('paciente_id',   pacienteId);
        fd.append('tipo_ficha_id', tipoFichaId);
        fd.append('data_ficha',    dataFicha);
        fd.append('arquivo',       f);

        try {
            const res  = await fetch('processar_upload.php', { method: 'POST', body: fd });
            const json = await res.json();

            document.getElementById(`barra_${i}`).style.width = '100%';

            if (json.ok) {
                ok++;
                document.getElementById(`barra_${i}`).style.background = '#198754';
                document.getElementById(`status_${i}`).innerHTML =
                    '<span class="text-success"><i class="bi bi-check-circle-fill"></i> Enviado</span>';
                document.getElementById(`prog_${i}`).style.background = '#f0fdf4';
            } else {
                falha++;
                document.getElementById(`barra_${i}`).style.background = '#dc3545';
                document.getElementById(`status_${i}`).innerHTML =
                    `<span class="text-danger" title="${escapar(json.msg)}"><i class="bi bi-x-circle-fill"></i> Erro</span>`;
                document.getElementById(`prog_${i}`).style.background = '#fff5f5';
            }
        } catch (e) {
            falha++;
            document.getElementById(`barra_${i}`).style.background = '#dc3545';
            document.getElementById(`status_${i}`).innerHTML =
                '<span class="text-danger"><i class="bi bi-x-circle-fill"></i> Falha de rede</span>';
        }

        // Atualiza barra geral
        const pct = Math.round(((i + 1) / total) * 100);
        document.getElementById('barraGeral').style.width = pct + '%';
        document.getElementById('txtProgresso').textContent = pct + '%';
    }

    // Resumo final
    const resumo = document.getElementById('resumoFinal');
    resumo.style.display = 'block';
    resumo.style.cssText += falha === 0
        ? 'background:#f0fdf4;border:1px solid #b7edc8;border-radius:10px;padding:12px 16px;'
        : 'background:#fff5f5;border:1px solid #f5c2c7;border-radius:10px;padding:12px 16px;';
    resumo.innerHTML = falha === 0
        ? `<i class="bi bi-check-circle-fill text-success me-2"></i>
           <strong>${ok} ficha(s)</strong> enviada(s) com sucesso!
           <a href="listar.php" class="ms-2 fw-semibold" style="color:#198754;">Ver fichas →</a>`
        : `<i class="bi bi-exclamation-circle-fill text-danger me-2"></i>
           <strong>${ok}</strong> enviada(s) com sucesso · <strong>${falha}</strong> com erro.`;

    if (falha === 0) {
        arquivosSelecionados = [];
        renderizarLista();
    } else {
        btnEnviar.disabled = false;
    }
});

// ── Utilitários ──────────────────────────────────────────────
function formatarTamanho(bytes) {
    if (bytes < 1024)       return bytes + ' B';
    if (bytes < 1024*1024)  return (bytes/1024).toFixed(1) + ' KB';
    return (bytes/1024/1024).toFixed(1) + ' MB';
}

function escapar(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

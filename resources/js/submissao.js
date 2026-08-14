const editor = document.getElementById('editor');
const botao = document.getElementById('btn-submeter');
const painel = document.getElementById('painel-resultado');

let tentativaAtual = null;

if (editor && botao && painel) {
    // Tab dentro do textarea precisa indentar, nao mudar de campo.
    editor.addEventListener('keydown', (e) => {
        if (e.key !== 'Tab') return;
        e.preventDefault();
        const { selectionStart: ini, selectionEnd: fim, value } = editor;
        editor.value = value.slice(0, ini) + '    ' + value.slice(fim);
        editor.selectionStart = editor.selectionEnd = ini + 4;
    });

    botao.addEventListener('click', () => submeter());
    painel.addEventListener('click', (e) => {
        if (e.target.closest('#btn-mais-ajuda')) pedirDica();
        if (e.target.closest('#btn-simplificar')) simplificarErro();
    });
}

async function submeter() {
    botao.disabled = true;
    botao.textContent = 'Executando...';
    painel.innerHTML = '';

    const dados = await enviar(botao.dataset.url, { codigo: editor.value });

    botao.disabled = false;
    botao.textContent = 'Submeter';

    if (!dados) {
        painel.innerHTML = aviso('Nao foi possivel executar o codigo agora. Tente de novo.');
        return;
    }

    tentativaAtual = dados.tentativa_id;
    desenhar(dados);
}

async function pedirDica() {
    const alvo = document.getElementById('area-dicas');
    const btn = document.getElementById('btn-mais-ajuda');
    btn.disabled = true;
    btn.textContent = 'Pensando...';

    const dados = await enviar(`/tentativa/${tentativaAtual}/dica`);

    btn.disabled = false;
    btn.textContent = 'Preciso de mais ajuda';

    if (!dados || !dados.texto) {
        btn.remove();
        return;
    }

    alvo.insertAdjacentHTML('beforeend', blocoTraduzivel({
        id: `dica-${dados.nivel}`,
        titulo: `Dica ${dados.nivel} de 4`,
        segmentos: dados.segmentos,
        cor: 'border-sky-200 bg-sky-50',
    }));

    if (!dados.tem_proxima) btn.remove();
}

async function simplificarErro() {
    const btn = document.getElementById('btn-simplificar');
    btn.disabled = true;
    btn.textContent = 'Simplificando...';

    const dados = await enviar(`/tentativa/${tentativaAtual}/erro-simples`);
    if (!dados) {
        btn.disabled = false;
        btn.textContent = 'Ver em portugues simples';
        return;
    }

    btn.outerHTML = blocoTraduzivel({
        id: 'erro-simples',
        titulo: 'O que aconteceu, em portugues simples',
        segmentos: dados.segmentos,
        cor: 'border-amber-200 bg-amber-50',
    });
}

async function enviar(url, corpo = null) {
    try {
        const resposta = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                Accept: 'application/json',
            },
            body: corpo ? JSON.stringify(corpo) : null,
        });

        if (!resposta.ok) throw new Error(`HTTP ${resposta.status}`);

        return await resposta.json();
    } catch (erro) {
        console.error(erro);
        return null;
    }
}

/**
 * Todo texto que o aluno pode querer em Libras nasce com a mesma estrutura:
 * segmentos clicaveis e um botao proprio de traducao.
 */
function blocoTraduzivel({ id, titulo, segmentos, cor }) {
    const partes = segmentos
        .map((s, i) => `<span class="segmento cursor-pointer rounded px-1 transition-colors"
                              data-indice="${i}" data-texto="${escapar(s)}">${escapar(s)}</span>`)
        .join(' ');

    return `
      <div class="rounded-md border ${cor} p-4">
        <div class="mb-2 flex items-center justify-between gap-3">
          <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">${titulo}</h3>
          <button type="button" class="btn-traduzir select-none shrink-0 rounded-md bg-sky-600 px-3 py-1 text-xs font-medium text-white hover:bg-sky-700"
                  data-bloco="${id}">Traduzir com VLibras</button>
        </div>
        <div class="bloco-traduzivel leading-relaxed" data-bloco="${id}">${partes}</div>
      </div>`;
}

function desenhar(dados) {
    const passaram = dados.testes.filter((t) => t.passou).length;

    const cabecalho = dados.passou
        ? `<div class="rounded-md bg-emerald-50 p-4 text-emerald-800">
             <p class="font-semibold">Todos os testes passaram.</p>
             <p class="text-sm">Voce concluiu este exercicio.</p>
           </div>`
        : `<div class="rounded-md bg-rose-50 p-4 text-rose-800">
             <p class="font-semibold">${passaram} de ${dados.total} testes passaram.</p>
           </div>`;

    const lista = dados.testes
        .map((t) => {
            const cor = t.passou ? 'text-emerald-600' : 'text-rose-600';
            const rotulo = t.passou ? 'passou' : t.status === 'timeout' ? 'demorou demais' : 'falhou';
            return `<li class="flex justify-between border-b border-slate-100 py-1.5 last:border-0">
                      <span>Teste ${t.indice}</span>
                      <span class="font-medium ${cor}">${rotulo}</span>
                    </li>`;
        })
        .join('');

    const diagnostico = dados.tipo_erro
        ? `<div class="rounded-md border border-slate-200 bg-white p-4">
             <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">O que aconteceu</h3>
             <span class="inline-block rounded-full bg-amber-100 px-3 py-1 text-sm font-medium text-amber-900">
               ${escapar(dados.tipo_erro_rotulo)}
             </span>
           </div>`
        : '';

    // Bloco 1 do escalonamento: o erro bruto, sem reescrita.
    const bruto = dados.stderr
        ? `<div class="rounded-md border border-slate-200 bg-white p-4">
             <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">Mensagem do sistema</h3>
             <pre class="overflow-x-auto whitespace-pre-wrap rounded bg-slate-900 p-3 font-mono text-xs text-slate-100">${escapar(dados.stderr)}</pre>
             <button type="button" id="btn-simplificar"
                     class="mt-3 rounded-md bg-amber-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-amber-700">
               Ver em portugues simples
             </button>
           </div>`
        : '';

    const ajuda = dados.passou
        ? ''
        : `<div id="area-dicas" class="space-y-3"></div>
           <button type="button" id="btn-mais-ajuda"
                   class="w-full rounded-md border border-sky-300 bg-white px-4 py-2 text-sm font-medium text-sky-700 hover:bg-sky-50 disabled:opacity-60">
             Preciso de mais ajuda
           </button>`;

    painel.innerHTML = `${cabecalho}
        <ul class="rounded-md border border-slate-200 bg-white p-4 text-sm">${lista}</ul>
        ${diagnostico}${bruto}${ajuda}`;
}

function aviso(texto) {
    return `<div class="rounded-md bg-amber-50 p-4 text-amber-800">${texto}</div>`;
}

function escapar(texto) {
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
}

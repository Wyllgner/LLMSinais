import {
    criarEditor,
    limparMarcacaoDeErro,
    linhaDoErro,
    marcarLinhaComErro,
    pegarCodigo,
} from './editor';

const area = document.getElementById('editor');
const botao = document.getElementById('btn-submeter');
const painel = document.getElementById('painel-resultado');

let tentativaAtual = null;

if (area && botao && painel) {
    criarEditor(area, area.dataset.codigo ?? '');

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
    limparMarcacaoDeErro();

    const dados = await enviar(botao.dataset.url, { codigo: pegarCodigo() });

    botao.disabled = false;
    botao.textContent = 'Submeter';

    if (!dados) {
        painel.innerHTML = aviso('Nao foi possivel executar o codigo agora. Tente de novo.');
        return;
    }

    tentativaAtual = dados.tentativa_id;

    const linha = linhaDoErro(dados.stderr);
    if (linha) marcarLinhaComErro(linha);

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
        cor: 'clay-btn-lilas',
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
        cor: 'clay-btn-pessego',
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
        .map((s, i) => `<span class="segmento cursor-pointer"
                              data-indice="${i}" data-texto="${escapar(s)}">${escapar(s)}</span>`)
        .join(' ');

    return `
      <div class="clay p-5">
        <div class="mb-3 flex items-center justify-between gap-3">
          <h3 class="text-sm font-bold uppercase tracking-wide text-argila-tinta-fraca">${titulo}</h3>
          <button type="button" class="btn-traduzir clay-btn ${cor} shrink-0 px-4 py-1 text-xs"
                  data-bloco="${id}">Traduzir</button>
        </div>
        <div class="bloco-traduzivel leading-relaxed" data-bloco="${id}">${partes}</div>
      </div>`;
}

function desenhar(dados) {
    const passaram = dados.testes.filter((t) => t.passou).length;

    const cabecalho = dados.passou
        ? `<div class="clay flex items-center gap-4 p-5">
             <span class="clay-selo flex h-12 w-12 shrink-0 items-center justify-center bg-menta text-xl text-menta-forte">✓</span>
             <div>
               <p class="font-bold">Todos os testes passaram.</p>
               <p class="text-sm text-argila-tinta-fraca">Voce concluiu este exercicio.</p>
             </div>
           </div>`
        : `<div class="clay flex items-center gap-4 p-5">
             <span class="clay-selo flex h-12 w-12 shrink-0 items-center justify-center bg-rosa text-lg font-bold text-rosa-forte">
               ${passaram}/${dados.total}
             </span>
             <p class="font-bold">${passaram} de ${dados.total} testes passaram.</p>
           </div>`;

    const lista = dados.testes
        .map((t) => {
            const cor = t.passou ? 'bg-menta text-menta-forte' : 'bg-rosa text-rosa-forte';
            const rotulo = t.passou ? 'passou' : t.status === 'timeout' ? 'demorou demais' : 'falhou';
            return `<li class="flex items-center justify-between gap-3">
                      <span class="text-sm font-medium">Teste ${t.indice}</span>
                      <span class="clay-selo px-3 py-1 text-xs font-bold ${cor}">${rotulo}</span>
                    </li>`;
        })
        .join('');

    const diagnostico = dados.tipo_erro
        ? `<div class="clay p-5">
             <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-argila-tinta-fraca">O que aconteceu</h3>
             <span class="clay-selo inline-block bg-limao px-4 py-1.5 text-sm font-bold text-limao-forte">
               ${escapar(dados.tipo_erro_rotulo)}
             </span>
           </div>`
        : '';

    // Bloco 1 do escalonamento: o erro bruto, sem reescrita.
    const bruto = dados.stderr
        ? `<div class="clay p-5">
             <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-argila-tinta-fraca">Mensagem do sistema</h3>
             <pre class="clay-cava overflow-x-auto whitespace-pre-wrap bg-argila-fundo p-4 font-mono text-xs">${escapar(dados.stderr)}</pre>
             <button type="button" id="btn-simplificar" class="clay-btn clay-btn-pessego mt-4 px-5 py-2 text-xs">
               Ver em portugues simples
             </button>
           </div>`
        : '';

    const ajuda = dados.passou
        ? ''
        : `<div id="area-dicas" class="space-y-3"></div>
           <button type="button" id="btn-mais-ajuda" class="clay-btn clay-btn-lilas w-full px-4 py-3 text-sm">
             Preciso de mais ajuda
           </button>`;

    painel.innerHTML = `${cabecalho}
        <ul class="clay space-y-2.5 p-5">${lista}</ul>
        ${diagnostico}${bruto}${ajuda}`;
}

function aviso(texto) {
    return `<div class="clay p-5 font-medium text-pessego-forte">${texto}</div>`;
}

function escapar(texto) {
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
}

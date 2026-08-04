const editor = document.getElementById('editor');
const botao = document.getElementById('btn-submeter');
const painel = document.getElementById('painel-resultado');

if (editor && botao && painel) {
    // Tab dentro do textarea precisa indentar, nao mudar de campo.
    editor.addEventListener('keydown', (e) => {
        if (e.key !== 'Tab') return;
        e.preventDefault();
        const { selectionStart: ini, selectionEnd: fim, value } = editor;
        editor.value = value.slice(0, ini) + '    ' + value.slice(fim);
        editor.selectionStart = editor.selectionEnd = ini + 4;
    });

    botao.addEventListener('click', async () => {
        botao.disabled = true;
        botao.textContent = 'Executando...';
        painel.innerHTML = '';

        try {
            const resposta = await fetch(botao.dataset.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ codigo: editor.value }),
            });

            if (!resposta.ok) throw new Error(`HTTP ${resposta.status}`);
            desenhar(await resposta.json());
        } catch (erro) {
            painel.innerHTML = aviso('Nao foi possivel executar o codigo agora. Tente de novo.');
            console.error(erro);
        } finally {
            botao.disabled = false;
            botao.textContent = 'Submeter';
        }
    });
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
             <p class="text-sm">Veja o que aconteceu abaixo.</p>
           </div>`;

    const lista = dados.testes
        .map((t) => {
            const cor = t.passou ? 'text-emerald-600' : 'text-rose-600';
            const rotulo = t.passou ? 'passou' : (t.status === 'timeout' ? 'demorou demais' : 'falhou');
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
           </div>`
        : '';

    painel.innerHTML = `
        ${cabecalho}
        <ul class="rounded-md border border-slate-200 bg-white p-4 text-sm">${lista}</ul>
        ${diagnostico}
        ${bruto}
    `;
}

function aviso(texto) {
    return `<div class="rounded-md bg-amber-50 p-4 text-amber-800">${texto}</div>`;
}

function escapar(texto) {
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
}

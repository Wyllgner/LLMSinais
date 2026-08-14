/**
 * Sincroniza o texto escrito com o sinal exibido pelo avatar do VLibras.
 *
 * O widget oficial nao expoe API publica para traduzir um trecho sob comando
 * nem eventos de progresso por sinal. Por isso a granularidade e o segmento,
 * uma frase curta, e nao a palavra. O aluno controla o avanco.
 */

const CLASSES_REALCE = ['bg-yellow-200', 'ring-2', 'ring-yellow-400'];

class Sincronizador {
    constructor() {
        this.blocoAtivo = null;
        this.indice = -1;
    }

    get segmentos() {
        return this.blocoAtivo
            ? [...this.blocoAtivo.querySelectorAll('.segmento')]
            : [];
    }

    ativar(bloco, indice = 0) {
        if (this.blocoAtivo && this.blocoAtivo !== bloco) {
            this.limparRealce(this.blocoAtivo);
        }

        this.blocoAtivo = bloco;
        this.irPara(indice);
    }

    irPara(indice) {
        const segmentos = this.segmentos;
        if (indice < 0 || indice >= segmentos.length) return;

        this.indice = indice;
        this.realcar(segmentos[indice]);
        this.enviarAoVLibras(segmentos[indice]);
        this.atualizarControles();
    }

    anterior() {
        this.irPara(this.indice - 1);
    }

    proximo() {
        this.irPara(this.indice + 1);
    }

    repetir() {
        if (this.indice >= 0) this.irPara(this.indice);
    }

    realcar(alvo) {
        this.limparRealce(this.blocoAtivo);
        alvo.classList.add(...CLASSES_REALCE);
        alvo.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    limparRealce(bloco) {
        bloco?.querySelectorAll('.segmento').forEach((s) => s.classList.remove(...CLASSES_REALCE));
    }

    /**
     * Plano A: seleciona o trecho no DOM, que e o gatilho que o widget escuta.
     */
    enviarAoVLibras(elemento) {
        const selecao = window.getSelection();
        if (!selecao) return;

        const intervalo = document.createRange();
        intervalo.selectNodeContents(elemento);
        selecao.removeAllRanges();
        selecao.addRange(intervalo);

        document.dispatchEvent(new MouseEvent('mouseup', { bubbles: true, cancelable: true }));

        // A selecao nativa pinta por cima do realce amarelo. O widget ja leu o
        // texto no mouseup, entao ela pode sair e deixar o realce aparecer.
        setTimeout(() => window.getSelection()?.removeAllRanges(), 150);
    }

    /**
     * Plano B: o aluno seleciona o texto com o mouse, comportamento nativo do
     * widget, e o realce acompanha. Depende so de API padrao do navegador.
     */
    acompanharSelecaoManual() {
        document.addEventListener('selectionchange', () => {
            const texto = window.getSelection()?.toString().trim();
            if (!texto || texto.length < 3) return;

            const alvo = [...document.querySelectorAll('.segmento')].find(
                (s) => s.dataset.texto.includes(texto) || texto.includes(s.dataset.texto)
            );

            if (!alvo) return;

            const bloco = alvo.closest('.bloco-traduzivel');
            if (bloco !== this.blocoAtivo) {
                this.limparRealce(this.blocoAtivo);
                this.blocoAtivo = bloco;
            }

            this.indice = this.segmentos.indexOf(alvo);
            this.realcar(alvo);
            this.atualizarControles();
        });
    }

    atualizarControles() {
        const barra = document.getElementById('controles-vlibras');
        if (!barra) return;

        const total = this.segmentos.length;

        // A barra nasce com hidden. Tirar a classe deixaria display block, e os
        // alinhamentos dela dependem de flex.
        barra.classList.toggle('hidden', total === 0);
        barra.classList.toggle('flex', total > 0);

        const posicao = document.getElementById('posicao-segmento');
        if (posicao) posicao.textContent = total ? `${this.indice + 1} de ${total}` : '';

        barra.querySelector('[data-acao="anterior"]').disabled = this.indice <= 0;
        barra.querySelector('[data-acao="proximo"]').disabled = this.indice >= total - 1;
    }
}

export const sincronizador = new Sincronizador();

/**
 * O widget nasce fechado, esperando clique no botao de acesso. Como o botao
 * fica escondido, o clique e disparado por codigo assim que o plugin monta.
 */
function abrirWidget() {
    const area = document.getElementById('area-vlibras');
    if (!area) return;

    let tentativas = 0;

    const timer = setInterval(() => {
        const botao = area.querySelector('[vw-access-button]');
        const aberto = area.querySelector('canvas');

        if (aberto) {
            clearInterval(timer);
            return;
        }

        if (botao) botao.click();

        if (++tentativas > 40) clearInterval(timer);
    }, 500);
}

/**
 * O widget traduz o texto do elemento clicado. Sem isso, clicar em Proximo faz
 * o avatar soletrar a palavra PROXIMO no lugar da frase.
 */
function protegerControles(seletor) {
    document.addEventListener(
        'mousedown',
        (e) => {
            if (e.target.closest(seletor)) {
                e.preventDefault();
                e.stopPropagation();
            }
        },
        true
    );

    document.addEventListener(
        'mouseup',
        (e) => {
            // O mouseup sintetico tem o document como alvo e precisa passar.
            if (e.target !== document && e.target.closest?.(seletor)) {
                e.stopPropagation();
            }
        },
        true
    );
}

export function iniciarVLibras() {
    sincronizador.acompanharSelecaoManual();
    protegerControles('#controles-vlibras, .btn-traduzir, #btn-submeter, #btn-mais-ajuda, #btn-simplificar');
    abrirWidget();

    // Delegacao: os blocos de erro e feedback so existem depois da submissao.
    document.addEventListener('click', (e) => {
        const botao = e.target.closest('.btn-traduzir');
        if (botao) {
            const bloco = document.querySelector(`.bloco-traduzivel[data-bloco="${botao.dataset.bloco}"]`);
            if (bloco) sincronizador.ativar(bloco);
            return;
        }

        const segmento = e.target.closest('.segmento');
        if (segmento) {
            const bloco = segmento.closest('.bloco-traduzivel');
            sincronizador.ativar(bloco, [...bloco.querySelectorAll('.segmento')].indexOf(segmento));
            return;
        }

        const acao = e.target.closest('#controles-vlibras [data-acao]')?.dataset.acao;
        if (acao === 'anterior') sincronizador.anterior();
        if (acao === 'repetir') sincronizador.repetir();
        if (acao === 'proximo') sincronizador.proximo();
    });
}

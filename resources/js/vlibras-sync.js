/**
 * Sincroniza o texto escrito com o sinal exibido pelo avatar do VLibras.
 *
 * O widget traduz um trecho sob comando, por window.plugin.translate, mas nao
 * emite evento de progresso por sinal. Sem saber quando cada sinal termina, a
 * granularidade e o segmento, uma frase curta, e nao a palavra. O aluno
 * controla o avanco.
 */

const CLASSES_REALCE = ['segmento-ativo'];

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
     * O plugin expoe window.plugin.translate, que e como ele traduz o proprio
     * texto internamente. O trecho vem do data-texto, entao o que o avatar
     * recebe e exatamente o segmento, sem depender de selecao nem de DOM.
     */
    enviarAoVLibras(elemento) {
        const texto = elemento.dataset.texto?.trim();
        if (texto) window.plugin?.translate(texto);
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
 * A captura de texto por clique do widget e o que faz o avatar soletrar
 * PROXIMO. Ela nao e configuravel: o plugin sempre chama
 * loadTextCaptureScript() ao abrir, e o script registra
 * document.addEventListener('click', handler, true). O handler traduz o
 * innerText do elemento clicado e ainda chama preventDefault e
 * stopPropagation. Como todo BUTTON entra na regra dele, nenhum botao da tela
 * escapa, e o clique tambem nao chega aos nossos proprios listeners.
 *
 * Tentativas que nao resolveram: preventDefault e stopPropagation no mousedown
 * e no mouseup em fase de captura, e select-none nos botoes. Nenhuma toca o
 * evento que ele de fato escuta, que e o click.
 *
 * Aqui a instalacao do listener e barrada na origem. O resto do fluxo de
 * eventos da pagina continua normal, e a traducao passa a sair so de
 * window.plugin.translate, chamado por nos com o texto exato do segmento.
 *
 * Isto depende do funcionamento interno do widget. Se uma versao futura mudar
 * a forma de registrar o listener, o sintoma volta a ser o avatar soletrando
 * o rotulo dos botoes.
 */
function bloquearCapturaDeCliqueDoWidget() {
    const original = document.addEventListener.bind(document);

    document.addEventListener = function (tipo, ouvinte, opcoes) {
        const captura = opcoes === true || opcoes?.capture === true;

        if (tipo === 'click' && captura) return;

        return original(tipo, ouvinte, opcoes);
    };
}

export function iniciarVLibras() {
    bloquearCapturaDeCliqueDoWidget();
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

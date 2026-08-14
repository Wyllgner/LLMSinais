/**
 * Sincroniza o texto escrito com o sinal exibido pelo avatar do VLibras.
 *
 * São duas granularidades ao mesmo tempo. O segmento, uma frase curta, e a
 * unidade que o aluno controla: ele escolhe qual frase traduzir e avanca no
 * proprio ritmo. Dentro da frase, o realce acompanha sinal a sinal, guiado
 * pelo evento response:glosa, que o player dispara a cada sinal executado.
 */

import { alinhar } from './glosa';
import { fecharMedicao, iniciarMedicao } from './medicao';

const CLASSES_REALCE = ['segmento-ativo'];
const CLASSE_PALAVRA = 'palavra-ativa';

class Sincronizador {
    constructor() {
        this.blocoAtivo = null;
        this.indice = -1;

        // Estado do realce por sinal, refeito a cada traducao.
        this.palavras = [];
        this.alinhamento = null;
        this.glosa = null;
        this.fraseEnviada = '';
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
        this.limparPalavra();
    }

    /**
     * O plugin expoe window.plugin.translate, que e como ele traduz o proprio
     * texto internamente. O trecho vem do data-texto, entao o que o avatar
     * recebe e exatamente o segmento, sem depender de selecao nem de DOM.
     */
    enviarAoVLibras(elemento) {
        const texto = elemento.dataset.texto?.trim();
        if (!texto) return;

        this.prepararParaSinais(elemento, texto);

        // A origem diz de que ponto da cadeia o texto veio, que e o que
        // permite comparar stderr bruto, erro simplificado e dica.
        this.origem = elemento.closest('.bloco-traduzivel')?.dataset.bloco ?? 'desconhecida';
        this.textoMedido = texto;

        iniciarMedicao(this.origem, texto);
        window.plugin?.translate(texto);
    }

    /**
     * O realce por sinal so faz sentido quando o que esta na tela e o que foi
     * enviado. Nos segmentos com parafrase de sinalizacao, o texto exibido e
     * outro, entao a frase inteira fica realcada e nada e realcado por dentro.
     */
    prepararParaSinais(elemento, textoEnviado) {
        this.alinhamento = null;
        this.palavras = [];

        if (elemento.textContent.trim() !== textoEnviado) return;

        this.dividirEmPalavras(elemento);
        this.palavras = [...elemento.querySelectorAll('.palavra')];
        this.fraseEnviada = textoEnviado;
    }

    dividirEmPalavras(elemento) {
        if (elemento.dataset.dividido === 'sim') return;

        const partes = elemento.textContent.split(/(\s+)/);
        elemento.textContent = '';

        partes.forEach((parte) => {
            if (parte === '') return;

            if (/^\s+$/.test(parte)) {
                elemento.appendChild(document.createTextNode(parte));
                return;
            }

            const span = document.createElement('span');
            span.className = 'palavra';
            span.textContent = parte;
            elemento.appendChild(span);
        });

        elemento.dataset.dividido = 'sim';
    }

    /**
     * Chamado a cada sinal executado pelo avatar. A glosa so fica disponivel
     * depois que a traducao volta do servidor, entao o alinhamento e feito no
     * primeiro sinal, nao no envio.
     */
    aoExecutarSinal(indice, total) {
        const glosa = window.plugin?.player?.gloss;

        if (glosa && !this.alinhamento) {
            this.glosa = glosa.split(/\s+/).filter(Boolean);
            this.alinhamento = this.palavras.length ? alinhar(this.fraseEnviada, glosa) : [];
        }

        this.mostrarSinalAtual(indice, total);

        // O ultimo sinal fecha a janela de medicao. O evento animation:end nao
        // serve: ele tambem dispara na largada, antes de qualquer sinal.
        if (indice >= total - 1) this.encerrarMedicao();

        if (!this.palavras.length) return;

        const alvo = this.alinhamento[indice];

        this.limparPalavra();

        // Sinal sem palavra correspondente mantem a frase realcada e segue.
        if (alvo === null || alvo === undefined) return;

        this.palavras[alvo]?.classList.add(CLASSE_PALAVRA);
    }

    encerrarMedicao() {
        if (this.textoMedido) fecharMedicao(this.origem, this.textoMedido);
    }

    limparPalavra() {
        document
            .querySelectorAll('.' + CLASSE_PALAVRA)
            .forEach((p) => p.classList.remove(CLASSE_PALAVRA));
    }

    /**
     * Mostra qual sinal esta sendo executado. Serve ao aluno e serve tambem
     * para tornar visivel o que o avatar entendeu da frase.
     */
    mostrarSinalAtual(indice, total) {
        const painel = document.getElementById('sinal-atual');
        if (!painel) return;

        const sinal = this.glosa?.[indice];

        painel.textContent = sinal ? `${sinal}  ·  sinal ${indice + 1} de ${total}` : '';
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

/**
 * O player e um EventEmitter e avisa a cada sinal executado, com o indice
 * dentro da glosa e o total. E o que torna possivel o realce sinal a sinal.
 *
 * Ele so existe depois que o plugin carrega sob demanda, entao a inscricao
 * espera por ele.
 */
function ouvirSinais() {
    const timer = setInterval(() => {
        const player = window.plugin?.player;
        if (!player?.on) return;

        clearInterval(timer);
        player.on('response:glosa', (indice, total) => sincronizador.aoExecutarSinal(indice, total));
    }, 500);

    setTimeout(() => clearInterval(timer), 30000);
}

export function iniciarVLibras() {
    bloquearCapturaDeCliqueDoWidget();
    abrirWidget();
    ouvirSinais();

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

/**
 * Mede o que o avatar realmente faz com o texto que recebe.
 *
 * O player roda em WebAssembly e escreve o diagnostico dele por Module.print.
 * Quando um sinal nao existe no dicionario, ele registra a queda para
 * datilologia antes de soletrar:
 *
 *     Sinal "INPUT" foi não carregado corretamente.
 *     ~~ To spell: INPUT
 *
 * Interceptar essa saida e o unico jeito de saber, de fora, que a traducao
 * degradou. O Module e recriado quando o player recarrega, entao o gancho e
 * reinstalado a cada traducao: instalar uma vez so leva a medicao silenciosa
 * de zero, que e pior do que nao medir.
 */

const SOLETRACAO = /~~ To spell:\s*(.+)/;

let linhas = [];
let envioAgendado = null;

function moduloDoPlayer() {
    return window.plugin?.player?.player?.Module ?? null;
}

/**
 * @param {string} origem  de onde veio o texto: conteudo, enunciado, dica…
 * @param {string} texto   o trecho enviado ao avatar
 */
export function iniciarMedicao(origem, texto) {
    const modulo = moduloDoPlayer();
    if (!modulo) return;

    linhas = [];

    const anterior = modulo.print;
    modulo.print = (...args) => {
        linhas.push(args.join(' '));
        anterior?.(...args);
    };

    clearTimeout(envioAgendado);

    // O player nao avisa quando a frase inteira termina, so quando cada sinal
    // comeca. A janela cobre a frase mais longa que o sistema gera.
    envioAgendado = setTimeout(() => enviar(origem, texto), 20000);
}

/**
 * Chamado quando o ultimo sinal comeca. O player avisa o inicio de cada sinal,
 * nunca o fim do ultimo, e a queda para datilologia so e registrada quando o
 * sinal tenta ser carregado. Por isso ainda ha uma espera aqui: fechar no
 * evento do ultimo sinal mede zero soletracao justamente na palavra que mais
 * interessa.
 */
export function fecharMedicao(origem, texto) {
    clearTimeout(envioAgendado);
    envioAgendado = setTimeout(() => enviar(origem, texto), 4000);
}

function enviar(origem, texto) {
    const glosa = window.plugin?.player?.gloss ?? null;

    const soletrados = [
        ...new Set(
            linhas
                .map((l) => l.match(SOLETRACAO)?.[1]?.trim())
                .filter(Boolean)
        ),
    ];

    linhas = [];

    const corpo = {
        origem,
        texto,
        glosa,
        sinais: glosa ? glosa.split(/\s+/).filter(Boolean).length : 0,
        soletrados,
    };

    fetch('/medicao', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            Accept: 'application/json',
        },
        body: JSON.stringify(corpo),
        keepalive: true,
    }).catch(() => {
        // A medicao e instrumentacao, nao funcionalidade. Falhar aqui nao pode
        // atrapalhar a aula.
    });
}

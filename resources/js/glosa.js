/**
 * Alinhamento entre a frase em portugues e a glosa que o avatar executa.
 *
 * O player informa qual sinal esta em execucao, mas so pelo indice dentro da
 * glosa. A glosa nao tem uma palavra para cada palavra da frase: artigo e
 * preposicao somem, o verbo volta para o infinitivo, e um sinal pode carregar
 * duas opcoes separadas por &, como ORDEM&ORDENAR.
 *
 * Por isso o casamento e por semelhanca de radical, sempre da esquerda para a
 * direita. Sinal que nao acha palavra fica sem realce, o que e melhor do que
 * realcar a palavra errada.
 */

// Marcas de pontuacao que a glosa representa como sinal proprio, ex: [PONTO].
const MARCA = /^\[.*\]$/;

const NOTA_MINIMA = 0.6;

function normalizar(texto) {
    return texto
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toUpperCase()
        .replace(/[^A-Z0-9]/g, '');
}

/**
 * 1 quando as palavras sao iguais. Abaixo disso, a fracao do radical comum,
 * que e o que aproxima "REPETE" de "REPETIR".
 */
function semelhanca(a, b) {
    if (!a || !b) return 0;
    if (a === b) return 1;

    let comum = 0;
    while (comum < a.length && comum < b.length && a[comum] === b[comum]) comum++;

    return comum < 3 ? 0 : comum / Math.max(a.length, b.length);
}

/**
 * @return {Array<number|null>} para cada sinal da glosa, o indice da palavra
 *   correspondente na frase, ou null quando nao ha correspondencia.
 */
export function alinhar(frase, glosa) {
    const palavras = frase.split(/\s+/).filter(Boolean).map(normalizar);
    const sinais = glosa.split(/\s+/).filter(Boolean);

    let ponteiro = 0;

    return sinais.map((sinal) => {
        if (MARCA.test(sinal)) return null;

        const alternativas = sinal.split('&').map(normalizar);

        let melhor = null;
        let melhorNota = 0;

        for (let i = ponteiro; i < palavras.length; i++) {
            const nota = Math.max(...alternativas.map((a) => semelhanca(a, palavras[i])));

            if (nota > melhorNota) {
                melhorNota = nota;
                melhor = i;
            }

            if (nota === 1) break;
        }

        if (melhor === null || melhorNota < NOTA_MINIMA) return null;

        // Monotonico: o proximo sinal so procura daqui para a frente.
        ponteiro = melhor + 1;

        return melhor;
    });
}

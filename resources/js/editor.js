/**
 * O editor de codigo do aluno. Antes era um textarea; virou CodeMirror para a
 * tela parecer um ambiente de programacao de verdade, com numero de linha e
 * destaque de sintaxe.
 */

import { EditorView, basicSetup } from 'codemirror';
import { EditorState, StateEffect, StateField } from '@codemirror/state';
import { Decoration, keymap } from '@codemirror/view';
import { indentUnit } from '@codemirror/language';
import { indentWithTab } from '@codemirror/commands';
import { python } from '@codemirror/lang-python';
import { HighlightStyle, syntaxHighlighting } from '@codemirror/language';
import { tags } from '@lezer/highlight';

/**
 * O editor acompanha a paleta de argila do resto da tela. Um tema escuro de
 * prateleira brigaria com as superficies pastel.
 */
const CORES = HighlightStyle.define([
    { tag: tags.keyword, color: '#6b57d2', fontWeight: '600' },
    { tag: [tags.string, tags.special(tags.string)], color: '#12795b' },
    { tag: tags.number, color: '#b45816' },
    { tag: [tags.function(tags.variableName), tags.definition(tags.variableName)], color: '#b0304a' },
    { tag: tags.comment, color: '#8b86a6', fontStyle: 'italic' },
    { tag: tags.operator, color: '#6f6a8d' },
]);

const TEMA = EditorView.theme({
    '&': { height: '100%', fontSize: '13px', color: '#2f2b45', backgroundColor: 'transparent' },
    '.cm-scroller': { fontFamily: 'ui-monospace, monospace', padding: '0.75rem 0' },
    '.cm-content': { caretColor: '#6b57d2' },
    '.cm-gutters': { backgroundColor: 'transparent', border: 'none', color: '#a8a3c0' },
    '.cm-activeLine': { backgroundColor: 'rgba(169, 155, 245, 0.12)' },
    '.cm-activeLineGutter': { backgroundColor: 'transparent', color: '#6b57d2' },
    '&.cm-focused': { outline: 'none' },
    '.cm-linha-erro': { backgroundColor: 'rgba(245, 163, 174, 0.45)' },
});

const marcarLinha = StateEffect.define();
const limparLinhas = StateEffect.define();

const LINHA_COM_ERRO = Decoration.line({ class: 'cm-linha-erro' });

/**
 * A linha apontada pelo interpretador fica marcada em vermelho, para o aluno
 * ligar a mensagem de erro ao lugar do codigo sem precisar contar linhas.
 */
const campoDeErro = StateField.define({
    create: () => Decoration.none,
    update(marcas, transacao) {
        marcas = marcas.map(transacao.changes);

        for (const efeito of transacao.effects) {
            if (efeito.is(limparLinhas)) marcas = Decoration.none;

            if (efeito.is(marcarLinha)) {
                const total = transacao.state.doc.lines;
                const numero = Math.min(Math.max(efeito.value, 1), total);
                const linha = transacao.state.doc.line(numero);
                marcas = marcas.update({ add: [LINHA_COM_ERRO.range(linha.from)] });
            }
        }

        return marcas;
    },
    provide: (campo) => EditorView.decorations.from(campo),
});

let vista = null;

export function criarEditor(elemento, codigoInicial) {
    vista = new EditorView({
        parent: elemento,
        state: EditorState.create({
            doc: codigoInicial,
            extensions: [
                basicSetup,
                python(),
                syntaxHighlighting(CORES),
                TEMA,
                campoDeErro,
                // Python nao aceita mistura de tabulacao com espaco.
                indentUnit.of('    '),
                keymap.of([indentWithTab]),
            ],
        }),
    });

    return vista;
}

export function pegarCodigo() {
    return vista ? vista.state.doc.toString() : '';
}

export function marcarLinhaComErro(numero) {
    if (!vista) return;

    vista.dispatch({ effects: [limparLinhas.of(null), marcarLinha.of(numero)] });
}

export function limparMarcacaoDeErro() {
    if (!vista) return;

    vista.dispatch({ effects: limparLinhas.of(null) });
}

/**
 * O traceback do Python nomeia o arquivo do sandbox. Interessa a ultima linha
 * citada, que e a mais proxima do codigo do aluno.
 */
export function linhaDoErro(stderr) {
    if (!stderr) return null;

    const ocorrencias = [...stderr.matchAll(/line (\d+)/g)];

    return ocorrencias.length ? Number(ocorrencias.at(-1)[1]) : null;
}

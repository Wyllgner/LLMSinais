/**
 * As abas de conteudo e enunciado. O aluno estuda o conceito e so depois vai
 * para o que ele precisa fazer, sem perder o editor de vista.
 */

const ATIVA = ['clay-btn-lilas'];
const INATIVA = ['text-argila-tinta-fraca'];

function trocar(nome) {
    document.querySelectorAll('.aba').forEach((aba) => {
        const ativa = aba.dataset.aba === nome;
        aba.classList.remove(...ATIVA, ...INATIVA);
        aba.classList.add(...(ativa ? ATIVA : INATIVA));
    });

    document.querySelectorAll('[data-painel]').forEach((painel) => {
        painel.hidden = painel.dataset.painel !== nome;
    });
}

export function iniciarAbas() {
    if (!document.querySelector('.aba')) return;

    trocar('conteudo');

    document.addEventListener('click', (e) => {
        const aba = e.target.closest('.aba');
        if (aba) trocar(aba.dataset.aba);
    });
}

# LLMSinais

Plataforma web de autoaprendizagem de Programação I para estudantes surdos.

O aluno percorre uma trilha, programa em um editor no navegador e recebe
feedback pedagógico gerado por uma LLM. O feedback não entrega a resposta: são
dicas em quatro níveis, que o levam a encontrar o próprio erro. Todo texto da
tela pode ser traduzido para Libras pelo VLibras, com o trecho em tradução
destacado sinal a sinal.

## O problema

O estudante surdo sinalizante lê português como segunda língua. Quando o
programa dele falha, o que aparece na tela é uma mensagem do interpretador:
densa, técnica e em inglês.

Traduzir essa mensagem automaticamente não resolve. Termo de programação em
geral não tem sinal estabelecido em Libras, e o avatar então **cai na
datilologia**, soletrando I-N-P-U-T letra por letra. Pior que isso: medimos
casos em que o termo não é soletrado, e sim **traduzido com confiança para um
sinal sem relação nenhuma com o conceito** — `range` vira `RANGER`, o verbo de
ranger os dentes. O aluno vê um sinal bem formado e errado, sem nenhum aviso de
que a tradução falhou.

A aposta do projeto é atacar a barreira no **feedback**, não na linguagem de
programação: o aluno aprende Python de verdade, e o texto que chega até ele é
gerado para ser traduzível.

## Como funciona

```
código do aluno
    ↓
sandbox Docker            execução isolada, com limite de tempo e memória
    ↓
casos de teste            aprovado ou reprovado, por comparação de saída
    ↓
classificador             tipo do erro por heurística sobre o stderr
    ↓
LLM                       dica do nível N, sob restrição de escrita
    ↓
portão de sinalizabilidade   reprova e regenera o que não é traduzível
    ↓
segmentação               uma frase curta por unidade de tradução
    ↓
VLibras                   avatar, com realce sinal a sinal
```

A LLM nunca decide se o código está certo: quem decide são os casos de teste e
o classificador. Ela só escreve a explicação, a partir de um diagnóstico que já
chegou pronto. Isso limita o espaço para alucinação.

### Feedback em quatro níveis

O aluno pede ajuda quantas vezes quiser, até o limite de quatro. Cada nível
recebe só a própria regra no prompt — descrever os quatro de uma vez fazia o
modelo antecipar a correção logo na primeira dica.

| Nível | O que revela |
|---|---|
| 1 | onde olhar, sem dizer o motivo |
| 2 | o conceito por trás do erro, sem citar o código do aluno |
| 3 | uma estratégia de verificação |
| 4 | a correção do trecho, e só dele |

Quando o aluno repete o mesmo tipo de erro no mesmo conceito, a dica ganha uma
frase que ensina um hábito para evitá-lo. O modelo recebe o tipo já
classificado, nunca o histórico bruto, e é proibido de citar tentativas
anteriores — ele sabe que há um padrão sem ter passado para fantasiar em cima.

### Acessibilidade linguística

- **Texto exibido e texto sinalizado são coisas diferentes.** O aluno lê
  "A ordem `input` espera a pessoa digitar", porque precisa aprender o termo;
  o avatar recebe "Uma ordem especial espera a pessoa digitar", que não cai em
  datilologia.
- **Portão de sinalizabilidade.** Antes de a dica chegar ao aluno, ela é medida:
  frase acima de 12 palavras, símbolo sem sinal ou termo sem sinal reprovam e
  disparam nova geração, com os termos problemáticos nomeados.
- **Léxico que aprende com o tradutor.** Tudo que o avatar soletrar em execução
  entra no léxico de termos sem sinal e passa a ser barrado na geração seguinte.
- **Duas granularidades de sincronização.** O segmento é a unidade de controle
  do aluno, que escolhe a frase e avança no próprio ritmo; dentro dele, o
  realce acompanha sinal a sinal.
- **Glossário por exercício**, com o termo em fonte de código e a explicação
  traduzível ao lado.

## Arquitetura

Laravel 13 com Blade e Tailwind 4, SQLite, e JavaScript sem framework.

| Camada | Onde |
|---|---|
| Execução isolada | `app/Services/SandboxService.php` |
| Correção por casos de teste | `app/Services/CorretorService.php` |
| Classificação do erro | `app/Services/ClassificadorErro.php` |
| Geração do feedback | `app/Services/FeedbackService.php` |
| Verificação de sinalizabilidade | `app/Services/SinalizabilidadeService.php` |
| Léxico de termos sem sinal | `app/Services/LexicoSinais.php` |
| Segmentação para tradução | `app/Services/SegmentadorService.php` |
| Progresso do aluno | `app/Services/ProgressoService.php` |
| Sincronização com o avatar | `resources/js/vlibras-sync.js`, `glosa.js` |
| Medição da tradução | `resources/js/medicao.js` |

O código do aluno roda em contêiner descartável, sem rede, com `--init`,
limite de memória e tempo, e o diretório temporário é apagado ao fim. O
servidor da aplicação nunca executa código do aluno.

Não há login. O aluno é identificado por um UUID de sessão, para a plataforma
poder ser usada sem cadastro.

## Rodando

Requisitos: PHP 8.3, Composer, Node 22, Docker.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate

# necessário para o feedback; o resto funciona sem
# OPENAI_API_KEY=...

docker pull python:3.11-alpine

php artisan migrate --seed
npm run build
php artisan serve
```

O usuário que roda o `php artisan serve` precisa estar no grupo `docker`.

### Relatórios

```bash
php artisan relatorio:custo             # tokens e custo por dica gerada
php artisan relatorio:sinalizabilidade  # índice previsto e datilologia medida
```

O segundo compara o índice que o verificador previu com o que o avatar de fato
fez, e lista os termos mais soletrados. Os preços em `OPENAI_PRECO_ENTRADA` e
`OPENAI_PRECO_SAIDA` precisam estar preenchidos para o custo sair diferente de
zero.

## Limitações conhecidas

- **Não foi avaliado com estudantes surdos.** O conteúdo segue evidência
  publicada sobre leitores surdos, mas preferência de leitor surdo quem
  responde é leitor surdo.
- **O VLibras tem limitações documentadas** em conteúdo dinâmico e em termos
  sem sinal, e há variação regional dos sinais. Ele é camada complementar, não
  garantia de acessibilidade.
- **O alinhamento palavra↔sinal é heurístico**, por radical comum. Acerta bem
  em frase curta com vocabulário comum, que é o texto que o sistema gera, e
  erra mais em frase longa.
- **A LLM não obedece as restrições de forma de maneira perfeita.** O portão de
  sinalizabilidade mede e regenera, mas não garante.
- **Trilha piloto com três exercícios**, cobrindo variáveis, condicionais e
  laços.
- **O bloqueio da captura de clique do VLibras depende de detalhe interno do
  widget.** Se uma versão futura registrar o listener de outro jeito, o avatar
  volta a soletrar o rótulo dos botões.
- Sem testes automatizados. As validações foram manuais.

## Perspectivas

**Acadêmica.** Fecha uma cadeia que a literatura tem em pedaços desconectados:
feedback de código por LLM assume leitor fluente na língua escrita; as
ferramentas brasileiras para surdos em programação trocam a linguagem ou
oferecem glossário e videoaula, sem gerar feedback sobre o código; e a
tradução por avatar é camada de apresentação genérica. A medição de
datilologia e de corrupção semântica em tempo de execução também produz um
corpus reaproveitável.

**Social.** Distribuição aberta é resposta direta a uma crítica que a área faz
a si mesma: os artefatos brasileiros de tecnologia inclusiva em geral não
chegam a ser validados nem disponibilizados para a comunidade. Web aberta, sem
cadastro, código e conteúdo livres.

**Continuidade.** O custo por dica é medido e o consumo é registrado por
chamada, o que permite estimar o custo por aluno em um semestre. Um modelo
local, executado na infraestrutura da instituição, é caminho realista para
custo marginal zero e para não enviar código de aluno a terceiros. A adoção
natural é por instituição — institutos federais, escolas bilíngues e
programas de capacitação — e não por venda direta a estudante.

## Licença

Código sob **MIT** (`LICENSE`). Conteúdo pedagógico sob **CC BY-SA 4.0**
(`LICENSE-CONTEUDO`).

O VLibras é um serviço do Governo Federal brasileiro e não faz parte deste
repositório; ele é carregado a partir de `vlibras.gov.br`.

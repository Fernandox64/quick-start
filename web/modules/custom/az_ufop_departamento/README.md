# az_ufop_departamento

Módulo customizado que completa o az_quickstart com o que faltava para
paridade com o site institucional em Laravel (o "modelo" que roda na porta
8097): distinção Notícia/Edital, Docente/Funcionário, e um tipo de conteúdo
"Curso" para as páginas de Graduação e Pós-Graduação.

## O que ele adiciona

- **Vocabulário `az_curso_categoria`** com os termos "Graduação" e
  "Pós-Graduação".
- **Tipo de conteúdo `az_curso`** (Curso): nome + link opcional + categoria
  (Graduação ou Pós-Graduação).
- **Termos "Notícia" e "Edital"** no vocabulário nativo `az_news_tags` — para
  marcar publicações do tipo `az_news` como uma ou outra, do mesmo jeito que
  o campo "Tipo" na tela de Notícias do site em Laravel.
- **Termos "Docente" e "Funcionário"** no vocabulário nativo
  `az_person_categories` — mesma ideia, para o tipo `az_person`.

Tudo o mais que o site em Laravel tem (Eventos, Sobre, Contato, Serviços via
cards, Pessoal, Notícias) já existe nativamente no az_quickstart — não
precisou de nada customizado (`az_event`, `az_flexible_page`,
`az_paragraphs_contact`, `az_cards`, `az_person`, `az_news`).

## Como funciona a visibilidade no menu

Diferente do site em Laravel (que tem um campo "mostrar no menu"), aqui a
visibilidade usa o mecanismo nativo do Drupal: crie ou remova um item de menu
apontando para a página da categoria (`/categoria-de-curso/graduacao` ou
`/categoria-de-curso/pos-graduacao`, ambas geradas automaticamente pelo
Pathauto) em **Estrutura > Menus**. Sem item de menu, a página continua
existindo, só não aparece na navegação — o mesmo comportamento do toggle do
Laravel, feito do jeito nativo do Drupal.

## Instalando em outro site com az_quickstart

1. Copie a pasta `az_ufop_departamento/` inteira para
   `web/modules/custom/` no site de destino.
2. `drush en az_ufop_departamento -y`

Isso recria o vocabulário, o tipo de conteúdo e os termos do zero — testado
neste próprio projeto (removendo tudo manualmente e reinstalando o módulo do
zero) antes de documentar aqui.

## Removendo

`drush pmu az_ufop_departamento -y` remove o tipo de conteúdo `az_curso`, o
vocabulário `az_curso_categoria` e seus termos. Os termos adicionados aos
vocabulários nativos (`az_news_tags`, `az_person_categories`) **não** são
removidos automaticamente, para não quebrar conteúdo já marcado com eles.

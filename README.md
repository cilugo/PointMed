# PointMed — documentação do projeto

> **Nome novo (05/10/2026):** o projeto se chamava **FacilMed** e virou **PointMed** porque já existe
> um app "FácilMED" de telemedicina. Os nomes internos ficam (pasta `FacilMed`, banco `facilmed`,
> e-mails `@facilmed.test`, comandos `facilmed:`); ver §6, entrada de 05/10.

> **Este é o documento único do projeto.** Tudo o que estava espalhado em 14 arquivos
> (PASSO_A_PASSO, RELATORIO_COMPLETO, PERFIL, AI_HANDOFF, MUDANCAS, REVISAO, LEIA-ME…) foi
> juntado aqui em 24/09/2026. As versões antigas continuam no histórico do Git.
>
> Na raiz ficam só mais dois textos, que as IAs leem sozinhas: **`AGENTS.md`** (regras para
> qualquer IA) e **`CLAUDE.md`** (aponta para o AGENTS.md).

TCC do curso Técnico em Desenvolvimento de Sistemas — Etec Profª Ilza Nascimento Pintus.
Entrega: **20/10/2026**.

## Sumário

1. [O que é o PointMed](#1-o-que-é-o-pointmed)
2. [Como rodar](#2-como-rodar) · [2.7 No servidor](#27-no-servidor-render--aiven--site-no-ar)
3. [Contas e dados de teste](#3-contas-e-dados-de-teste)
4. [Organização das pastas](#4-organização-das-pastas)
5. [Como o sistema funciona](#5-como-o-sistema-funciona)
6. [Estado atual e próximos passos](#6-estado-atual-e-próximos-passos)
7. [Mapa das telas internas](#7-mapa-das-telas-internas)
8. [Identidade visual e tom de voz](#8-identidade-visual-e-tom-de-voz)
9. [Decisões e o porquê](#9-decisões-e-o-porquê)
10. [Equipe, cronograma e regras de trabalho](#10-equipe-cronograma-e-regras-de-trabalho)
11. [Histórico de correções](#11-histórico-de-correções)
12. [Protótipo antigo](#12-protótipo-antigo)

---

## 1. O que é o PointMed

Guia de clínicas e hospitais perto do usuário. A pessoa busca por especialidade e localização
(localização do aparelho ou cidade), vê os locais do mais perto para o mais longe, os médicos
disponíveis em cada um, os convênios aceitos, a **faixa de preço** de cada unidade ($ a $$$$,
escolhida pela clínica) e as avaliações de outros usuários, com comentário — e avalia depois.

**Desde 01/10/2026 o PointMed não agenda consultas** (documento "Modificações - 01/10"): saíram
agenda, horários, lembretes por e-mail e a conta do médico. Ver §6.

| Quem usa | O que faz |
|---|---|
| **Usuário** | busca locais e médicos, filtra por convênio, avalia locais e médicos, cadastra carteirinha, foto de perfil |
| **Clínica / hospital** | unidades, médicos (cadastra e mantém o perfil), especialidades, faixa de preço de cada unidade, convênios, avaliações |
| **Admin** | contas, Verificar CNPJ, clínicas, especialidades, convênios, acompanhamento da plataforma |

O **médico não tem conta**: é um perfil cadastrado pela clínica onde ele atende.

**Tudo é fictício:** médicos, clínicas, convênios e as "bases oficiais" de CRM, CNPJ e
carteirinha são tabelas do próprio banco (**bases simuladas**). Nada é consultado no CFM, na
Receita ou em operadora de verdade — e nenhuma tela diz que é.

**O que o PointMed NÃO é** (vale para qualquer pessoa ou IA):
- não é prontuário eletrônico — nada de diagnóstico, receita, atestado, exame;
- não agenda consultas; não é telemedicina; não processa pagamento (só mostra a faixa de preço); não integra com o SUS;
- não guarda documento médico (o único upload é a foto de perfil);
- não dá nenhum tipo de parecer sobre a saúde de ninguém.

---

## 2. Como rodar

**Laravel 12 + MariaDB do XAMPP.** Telas em Blade com CSS próprio e Alpine.js. **Não precisa de Node/npm.**

### 2.1 Programas (uma vez por computador)

| Programa | Versão | Para quê |
|---|---|---|
| XAMPP | **8.2.12** (PHP 8.2) | PHP + banco MariaDB + Apache |
| Composer | 2.x | baixa o Laravel — https://getcomposer.org/Composer-Setup.exe |
| GitHub Desktop | qualquer | baixar e enviar o código |

Confira no terminal: `php -v` (tem que ser 8.2.x) e `composer -V`. Se o PHP for mais antigo,
instale o XAMPP 8.2.12 e coloque `C:\xampp\php` no `Path` do Windows.

**Libere duas extensões do PHP:** abra `C:\xampp\php\php.ini`, procure as linhas abaixo e
**apague o `;`** do começo (se já não tiver). Salve.

```
extension=zip
extension=fileinfo
```

### 2.2 Banco de dados

1. No painel do XAMPP, ligue o **MySQL**.
2. Em http://localhost/phpmyadmin → **Novo** → nome **`facilmed`**, cotejamento
   **`utf8mb4_unicode_ci`** → **Criar**.

### 2.3 Instalar (uma vez)

No GitHub Desktop: **Repository → Open in Command Prompt**. Depois:

```bash
composer run setup
```

Instala as dependências, cria o `.env`, gera a chave e monta o banco com os dados de teste. No
fim deve aparecer "12 avaliações de exemplo."

### 2.4 Abrir o sistema — dois jeitos

**A) Pelo XAMPP, sem terminal (o `index.php` da raiz):** a pasta do projeto dentro de
`C:\xampp\htdocs\` com o nome **`FacilMed`**, **Apache** e **MySQL** ligados →
**http://localhost/FacilMed**

**B) Pelo terminal:** `composer run dev` → **http://127.0.0.1:8000** (para parar: `Ctrl + C`).

### 2.5 Dia a dia

| Quero... | Comando |
|---|---|
| baixar o que o grupo mudou | **Pull** no GitHub Desktop, depois `composer install` e `php artisan migrate` |
| zerar o MEU banco e recriar os dados de teste | `composer run banco-do-zero` (apaga tudo do `facilmed` da sua máquina) |
| rodar os testes automáticos | `php artisan test` (usa o banco `facilmed_testes`, criado sozinho) |
| ver erro detalhado | final de `storage/logs/laravel.log` |
| ver o e-mail de "Esqueci minha senha" | também em `storage/logs/laravel.log` (na sua máquina, `MAIL_MAILER=log`: nenhum e-mail sai de verdade) |

### 2.6 Se algo falhar

| Sintoma | O que fazer |
|---|---|
| `The zip extension and unzip/7z commands are both missing` | faltou `extension=zip` no `php.ini` |
| erro de `fileinfo` / `Class "finfo" not found` | faltou `extension=fileinfo` |
| `Your PHP version (8.0...) does not satisfy` | PHP antigo; veja 2.1 |
| `Unknown database 'facilmed'` | faltou criar o banco (2.2) |
| `SQLSTATE[HY000] [2002]` | o MySQL do XAMPP está desligado |
| `No application encryption key` | `php artisan key:generate` |
| página em branco / erro 500 | última linha de `storage/logs/laravel.log` |
| `migrate:rollback` ou `migrate:refresh` falha em `convenios_e_planos_ficticios` ("Data truncated for column 'operadora_ans_id'") | é de propósito: convênio fictício não tem operadora e o `down()` não apaga dado. O banco fica pela metade: rode `composer run banco-do-zero` (nunca use rollback/refresh) |
| mudei algo em `config/` e não fez efeito | `php artisan optimize:clear` |
| "Usar minha localização" não funciona | o navegador só libera a localização em `localhost` ou `https`: abra por `http://localhost/FacilMed` (não pelo IP da rede). Se você negou a permissão, libere no cadeado da barra de endereço — ou escolha a cidade na lista |
| `localhost/FacilMed` dá "Not Found" do Apache | em `C:\xampp\apache\conf\httpd.conf`, `LoadModule rewrite_module` sem `#` na frente; reinicie o Apache |
| `localhost/FacilMed` abre sem estilo | a pasta não se chama `FacilMed` ou não está direto no `htdocs`: use o jeito B |
| Pull dá erro `untracked working tree files would be overwritten by merge: composer.lock` | o seu `composer.lock` foi criado antes de ele entrar no Git. Apague só o **seu** (`del composer.lock` na pasta do projeto) e faça o Pull de novo. **Não apague o do GitHub** |

Não vão para o Git (e está certo): `vendor/`, `.env`, `storage/logs/`. O **`composer.lock`** vai
(desde 28/09): ele deixa todos com as mesmas versões dos pacotes, e o build do Render (`Dockerfile`)
precisa dele. Sem o `composer.lock` na `main`, o site online para de atualizar.

### 2.7 No servidor (Render + Aiven) — site no ar

O sistema fica publicado em **https://facilmed.onrender.com** — **Render** (site, conta do Sidney)
com o banco **MySQL no Aiven** (`facilmed-mysql`, Nova York). Os dois são grátis e nenhum pede
cartão. **Cada push na `main` atualiza o site** em uns 5 minutos. Se o build der erro, a versão
anterior continua no ar.

**Como o push chega no Render:** o app do Render não está instalado na conta `cilugo` (dona do
repositório), então o Render não percebe o push sozinho. Quem avisa é a automação
`.github/workflows/deploy-render.yml` (aba **Actions** do GitHub), que chama o *Deploy Hook* do
Render guardado no segredo `RENDER_DEPLOY_HOOK` (GitHub → Settings → Secrets and variables →
Actions). Para publicar sem commit: aba Actions → "Publicar no Render" → **Run workflow**.

Arquivos do deploy (não mexem no XAMPP): `Dockerfile`, `docker/entrypoint.sh`, `render.yaml`,
`.dockerignore`. A cada vez que o servidor liga, o `entrypoint.sh`:
1. roda `php artisan migrate --force` (só aplica migration nova; não apaga nada);
2. se o banco estiver **vazio**, roda os seeders (as contas da §3 passam a existir no site);
3. troca a senha das contas de demonstração pela `DEMO_PASSWORD` (`facilmed:proteger-demonstracao`,
   12/10 — ver abaixo);
4. faz o cache de config, rotas e telas.

**Senha das contas de demonstração no site (12/10/2026).** O repositório é público e a §3 mostra a
senha `facilmed2026`. **No site no ar ela não vale:** o admin, as clínicas e os usuários do seed
usam a senha da variável **`DEMO_PASSWORD`**, que fica só no painel do Render (Environment) e com o
grupo. Para trocar, mude a variável e publique de novo (aba Actions → **Run workflow**): no boot, as
contas passam para a senha nova e quem estava logado nelas cai fora. **Sem a variável**, essas contas
ficam com uma senha aleatória (ninguém entra) e a aba **Logs** avisa. Na máquina de cada um e nos
testes nada muda: continua `facilmed2026`.

**Por que MySQL no Aiven e não o Postgres do Render?** O banco do grupo é o MariaDB do XAMPP
(mesma família do MySQL), as migrations usam recursos dele (`CHECK`, `ENUM`) e o Postgres grátis do
Render é apagado depois de 30 dias.

**Montar do zero (só uma vez):**
1. **Aiven** — em aiven.io, crie conta → *Create service* → **MySQL** → plano **Free**. Quando ficar
   *Running*, na página do serviço copie o **Service URI** (`mysql://avnadmin:...`) e, em
   *CA certificate*, clique em **Show** e copie o texto inteiro (de `-----BEGIN` até `END-----`).
2. **Render** — em render.com, entre com o GitHub → **New → Blueprint** → escolha o repositório
   `cilugo/PointMed` (era `cilugo/FacilMed`; o GitHub redireciona o nome antigo. Se não aparecer, clique em
   *Configure GitHub* e libere o acesso). O Render lê o
   `render.yaml` e pede 3 valores:
   - `APP_KEY`: a chave do Laravel (`base64:...`). Gerar: `php artisan key:generate --show`.
   - `DB_URL`: o *Service URI* do Aiven.
   - `MYSQL_CA_CERT`: o texto do certificado do Aiven.
   - `DEMO_PASSWORD`: a senha das contas de demonstração no site (mínimo 8, **nunca** `facilmed2026`).
3. **Apply**. O primeiro build leva uns 5–10 minutos. O endereço aparece no topo
   (`https://facilmed-xxxx.onrender.com`). Aba **Logs** mostra os erros, se houver.

**Limites do plano grátis (bom saber antes da banca):**
- O site **dorme depois de 15 minutos** sem visita; a primeira visita depois disso leva ~1 minuto.
  Abra o site uns 2 minutos antes de apresentar.
- O Aiven pode desligar o banco grátis depois de muito tempo **sem nenhum uso** (avisa por e-mail
  antes). Basta religar no painel.
- O único e-mail do sistema é o de "Esqueci minha senha". Ele sai de verdade pelo Brevo só se
  `BREVO_API_KEY` e `MAIL_FROM_ADDRESS` estiverem preenchidos no Render; sem eles, vai para o log.
- **Nunca** coloque senha do banco, `APP_KEY` ou `DEMO_PASSWORD` em arquivo do Git (nem no
  README, nem em mensagem de commit): elas ficam só no painel do Render.

**Apagar tudo e recriar os dados de demonstração no servidor:** no Render, aba **Shell**:
`php artisan migrate:fresh --seed --force` (⚠ apaga o que foi cadastrado no site). As contas
recriadas já nascem com a `DEMO_PASSWORD`.

---

## 3. Contas e dados de teste

**Senha de todas as contas: `facilmed2026`** — na sua máquina (XAMPP) e nos testes.
**No site no ar (facilmed.onrender.com) a senha é outra:** a `DEMO_PASSWORD` do Render, que fica só
com o grupo (§2.7). Nunca escreva essa senha aqui.

| Tipo | E-mail | Observação |
|---|---|---|
| Admin | `admin@facilmed.test` | |
| Paciente | `ana@facilmed.test` | CPF 802.301.401-30 · tem carteirinha SpSaúde Família ativa |
| Paciente | `marcos@facilmed.test` | CPF 802.301.402-11 · sem carteirinha |
| Clínica | `contato@clinicaspsaude.test` · `contato@vidaplena.test` · `contato@aurora.test` | São José dos Campos |
| Hospital | `contato@santaclara.test` (Taubaté) · `contato@saolucasdovale.test` (Jacareí) · `contato@hospitalesperanca.test` (Caçapava) | |

**Médicos (sem conta, desde 01/10/2026):** 8 perfis em `DadosFicticios::MEDICOS`, cada um em dois
lugares — Helena Navarro (Cardiologia, CRM 112233), Rafael Moreira (Dermatologia, 223344), Camila
Reis (Pediatria, 334455), Juliana Prado (Ginecologia, 556688), Marcelo Antunes (Ortopedia, 667788),
Beatriz Okada (Oftalmologia, 778899), Thiago Nunes (Endocrinologia, 889900) e Lucas Ferreira
(Neurologia, 990011). O `AvaliacaoSeeder` cria 12 avaliações de exemplo (Ana e Marcos).

Convênios fictícios: **SpSaúde**, **Horizonte Med**, **Bem Viver Saúde** (3 planos cada) e
**Vale Saúde** (desativado, para demonstrar). Todos os dados estão em
`database/seeders/DadosFicticios.php`.

### Para testar a distância (Locais perto de você)

- **Pela cidade:** `/locais?cidade=Jacareí` → São Lucas (Jacareí) primeiro, Santa Clara (Taubaté) por último.
- **Pela localização:** clique em "Usar minha localização" e permita no navegador (funciona em
  `localhost` e no site no ar, que é `https`). Quem está em São José vê as três clínicas de lá primeiro.
- **Com especialidade:** `/locais?especialidade=pediatria` → só Aurora e Esperança (Dra. Camila).
- **Com convênio:** `/locais?especialidade=endocrinologia&convenio=3` → só Aurora e Santa Clara (Dr. Thiago aceita Bem Viver Saúde).
- **Página do local:** clique no nome ou em "Ver local". Vida Plena, SpSaúde e Santa Clara têm nota; o comentário de outras pessoas não aparece. Logado como paciente, o formulário "Avalie este local" fica na coluna da direita.

### Para testar a exclusão de conta

Entre como **Marcos** → "Meu perfil" → "Excluir minha conta" (senha `facilmed2026` + a caixa de
confirmação). Ele volta para o login com o aviso, e o e-mail dele não entra mais. **Não dá para
desfazer:** para ter o Marcos de volta, `composer run banco-do-zero` (no site no ar, a aba Shell do
Render, §2.7). Depois de excluir, ele pode se cadastrar de novo com o mesmo e-mail e CPF.

### Para testar as bases simuladas

**Cadastro de médico pela clínica** (entre como clínica → **Meus médicos → Cadastrar médico**).
Desde 29/09 o médico não se cadastra sozinho: `/cadastro/medico` só volta para a escolha.

| CRM / UF | Resultado |
|---|---|
| 445566 / SP, nome **Paulo Yamada** | ✅ aceito — entra verificado, sem conta (desde 01/10); foto, bio e convênios em "Editar perfil" |
| 445566 / SP, outro nome | ❌ em nome de outra pessoa (desde 28/09 o nome é conferido; "Dr./Dra.", acento e maiúscula não contam) |
| 998877 / SP | ❌ cassado |
| 556677 / RJ | ❌ suspenso |
| 123456 / SP | ❌ não existe na base |
| 112233 / SP + CPF **701.201.301-06** | já está no PointMed (Dra. Helena): só ganha o vínculo com a unidade; se já atende nela, ❌ |
| 112233 / SP + outro CPF (ou sem CPF) | ❌ "Esse CPF não é o do médico com esse CRM" (desde 12/10: o CRM é público, o CPF não) |

**Cadastro de clínica/hospital** (`/cadastro/clinica`)

| CNPJ | Resultado |
|---|---|
| 43.300.001/0001-64 | ✅ aceito |
| 43.300.002/0001-09 | ❌ baixado |
| 12.345.678/0001-95 | ✅ aceito — CNPJ da Clínica Ferr Inni, conta de demonstração do grupo |
| 41.100.001/0001-95 | ❌ já cadastrado |
| 11.111.111/1111-11 | ❌ dígito verificador inválido |

**Carteirinha** (logado como paciente → "Meu plano")

| Paciente | Plano | Número | Resultado |
|---|---|---|---|
| Marcos | Bem Viver Individual | 300000000002 | ✅ aceita |
| Marcos | Horizonte Essencial | 200000000003 | ❌ vencida |
| Ana | Horizonte Empresarial | 200000000006 | ✅ aceita |
| Ana | Horizonte Família | 200000000004 | ❌ cancelada |
| Ana ou Marcos | SpSaúde Individual | 100000000005 | ❌ de outra pessoa |
| Marcos | SpSaúde Família | 100000000001 | ❌ é da Ana |

---

## 4. Organização das pastas

```
FacilMed/                  ← a pasta continua com o nome antigo (ver o aviso no topo)
├── README.md              ← este documento
├── AGENTS.md, CLAUDE.md   ← regras para IAs (ficam na raiz para elas acharem)
├── index.php, .htaccess   ← fazem http://localhost/FacilMed abrir o sistema pelo XAMPP
├── composer.json          ← dependências e os atalhos "composer run setup/dev/banco-do-zero"
├── Dockerfile, render.yaml, docker/, .github/  ← só para o site no ar (§2.7)
│
├── app/
│   ├── Http/Controllers/  ← um controller por tela: Usuario/, Clinica/, Admin/, Auth/ e, na raiz,
│   │                         Home, Busca, PerfilPublico, Cadastro e Foto
│   ├── Http/Requests/     ← validação dos formulários (Usuario/, Clinica/, Admin/, Auth/)
│   ├── Http/Middleware/   ← tipo de conta (GarantirTipoUsuario) e conta ativa (GarantirContaAtiva)
│   ├── Models/            ← tabelas e relacionamentos
│   ├── Policies/          ← quem pode mexer em quê (local, médico, vínculo, avaliação, carteirinha)
│   ├── Rules/             ← CPF, CNPJ, senha, CRM/CNPJ na base simulada
│   ├── Services/          ← BaseSimulada (confere CRM, CNPJ e carteirinha)
│   ├── Mail/              ← BrevoTransport (envio do e-mail pela API do Brevo)
│   ├── Console/Commands/  ← facilmed:garantir-admin (admin do Render) e facilmed:importar-operadoras
│   │                         (ANS; opcional desde 24/09, quando os convênios viraram 100% fictícios)
│   └── Support/           ← formatação, faixa de preço, foto de perfil, localização/distância, CEP
├── database/
│   ├── migrations/        ← estrutura do banco (fonte da verdade)
│   └── seeders/           ← dados fictícios (DadosFicticios.php)
├── resources/views/       ← telas (Blade)
│   ├── layouts/           ← site (público), auth (login), cadastro (cadastros), painel
│   ├── home, busca/, publico/, cadastro/, auth/
│   ├── usuario/, clinica/, admin/
│   └── painel/parciais/, components/  ← pedaços reaproveitados (cartões, foto, senha, horários)
├── public/                ← o que o navegador baixa: css/, javas/, imgs/
├── routes/                ← web.php (páginas) e auth.php (login e senha)
├── lang/pt_BR/            ← mensagens em português
├── config/                ← configurações (navegacao.php = menus,
│                             localizacao.php = coordenadas aproximadas das cidades)
├── tests/Feature/         ← 173 testes automáticos
├── storage/               ← logs e cache (gerado)
├── design/                ← prints e protótipos de tela (referência visual)
└── prototipo-antigo/      ← versão antiga em PHP puro (não usada pelo sistema)
```

---

## 5. Como o sistema funciona

### 5.1 A tabela central: `vinculos`

Um **vínculo** liga um médico a uma unidade (local) de uma clínica ou hospital. É nele que fica
o que depende de "onde o médico atende":

```
medico ──┐
         ├── vinculo ── aceita_particular, aceita_convenio, ativo
local ───┘
```

O mesmo médico pode atender em várias unidades, de clínicas diferentes. A clínica liga e desliga o
vínculo (Meus médicos → Desvincular) e o "aceita convênio" de cada um (Convênios).

### 5.2 As tabelas, por grupo

- **Contas:** `users` (a conta de acesso de todos: tipo `usuario`, `clinica` ou `admin`, status,
  `excluida_em`, foto), `usuarios` (perfil de quem busca: CPF, nascimento, sexo), `clinicas`.
- **Médicos:** `medicos` (perfil **sem conta**, mantido pela clínica), `medico_especialidade`,
  `convenio_medico` (o convênio é aceito pelo MÉDICO).
- **Locais:** `locais` (unidades; todo local é de uma clínica; `ativo`, `faixa_preco`, coordenada,
  média das avaliações), `horarios_funcionamento`, `vinculos`.
- **Especialidades e convênios:** `especialidades`, `convenios`, `planos`, `operadoras_ans`
  (opcional), `usuario_planos` (carteirinhas).
- **Galeria e site da unidade (13/10):** `locais.site` e `local_fotos` (até 6 por unidade; a imagem fica em `fotos`).
- **Avaliações:** `avaliacoes` — de um local **ou** de um médico, uma por usuário em cada.
- **Fotos:** `fotos` (a imagem em base64; ver §6, 07/10).
- **Bases simuladas:** `base_crms`, `base_cnpjs`, `base_carteirinhas` — fazem o papel do CFM,
  da Receita e das operadoras. **Só o seeder escreve nelas.**
- **Do Laravel:** `sessions`, `cache`, `jobs`, `password_reset_tokens`, `migrations`.

### 5.3 Faixa de preço, convênio e unidade ativa

- **Faixa de preço:** a clínica escolhe a faixa de cada unidade ($ a $$$$) em Clínica → Unidades.
  O que cada faixa quer dizer vem só de `App\Support\FaixaDePreco`. O usuário nunca vê valor em reais.
- **Convênio:** o médico aceita (perfil dele, editado pela clínica) **e** a clínica liga "aceita
  convênio" para ele naquela unidade. Toda tela com convênio mostra "Confirme na recepção se o seu
  plano é aceito neste endereço."
- **Unidade ativa ou desativada (12/10/2026):** a clínica edita os dados (nome, tipo, endereço,
  telefone) e desativa/reativa a unidade em Clínica → Unidades. Desativada, ela some da busca, da
  home e da página pública, e não recebe médico novo; os vínculos ficam como estavam e voltam a
  aparecer ao reativar. Pode desativar a última unidade, e a mensagem avisa que a clínica saiu da
  busca. Mudou o endereço? A coordenada é refeita (`Local::booted` e, com internet, o `Geocodificador`).

### 5.4 Regras que o sistema garante

- Médico só aparece em busca, listagem e perfil público com CRM **verificado**
  (`Medico::visivel`), e só nos lugares públicos: vínculo ativo, unidade ativa e clínica com a
  conta ativa (`Vinculo::publicos`).
- CRM, CNPJ e carteirinha são conferidos **na hora do cadastro**, na base simulada. O CRM é
  conferido **junto com o nome**.
- Médico não tem login; quem edita o perfil é uma clínica onde ele atende (`MedicoPolicy`). Nome,
  CRM e UF não mudam por tela.
- A clínica só mexe nas próprias unidades e vínculos (`LocalPolicy`, `VinculoPolicy`).
- Avaliação: só usuário logado, uma por local e uma por médico (avaliar de novo edita). A média é
  recalculada só pelo model `Avaliacao`. O comentário é público, com o autor como "Ana L.".
- Conta bloqueada é deslogada na próxima página, com a mensagem de conta bloqueada.
- **O usuário pode excluir a própria conta** (30/09, LGPD) em "Meu perfil", com a senha atual e uma
  confirmação. A conta é **anonimizada, não apagada** (`Usuario::excluirConta()`): carteirinhas,
  foto e comentários somem, as notas ficam na média, e nome, e-mail, telefone, CPF, nascimento e
  sexo são apagados. Conta excluída nunca volta a ativa (CHECK no banco).
- Várias regras também estão travadas **no próprio banco** (horário que fecha antes de abrir, nota
  fora de 1 a 5, faixa fora de 1 a 4, avaliação com dois alvos ou repetida).

### 5.5 E-mails

O PointMed não manda e-mail próprio desde 01/10/2026 (os avisos e lembretes de consulta saíram com
o agendamento). O único e-mail é o **"Esqueci minha senha"** do Breeze, que responde igual com ou
sem conta cadastrada.

- Na máquina de cada um: `MAIL_MAILER=log` — o e-mail vai para `storage/logs/laravel.log`.
- No Render: pela API do Brevo (`App\Mail\BrevoTransport`, `MAIL_MAILER=brevo` + `BREVO_API_KEY`).
  Falha no envio vira aviso na tela, não erro 500.
- **Enviar para caixa real só com aprovação do grupo, e só para e-mail de integrante.**

### 5.6 Locais perto de você (busca por distância)

```
onde fica o LOCAL    → ao salvar, a coordenada APROXIMADA do bairro ou do centro da cidade
                       (config/localizacao.php, Local::booted). No cadastro e na edição de
                       unidade, com internet, o Geocodificador troca pela do endereço (Nominatim).
onde está o USUÁRIO  → o navegador informa (botão "Usar minha localização"; é ele que pede a
                       permissão), o CEP digitado (ViaCEP + Nominatim; sem internet, o bairro/
                       cidade do CEP) ou o centro da cidade escolhida na lista.
distância            → linha reta (Haversine, App\Support\Localizacao), calculada em PHP.
```

- `/locais` (`BuscaController@locais`): só local público (`Local::publicos()`: unidade ativa, com
  médico visível e clínica ativa), do mais perto para o mais longe. Filtros: especialidade,
  convênio e raio de 5, 10 ou 20 km (só com origem). Sem origem, em ordem alfabética. Local sem
  coordenada vai para o fim, sem distância (e fica fora quando há raio).
- `/local/{id}` (`PerfilPublicoController@local`): endereço, horários, nota, faixa de preço,
  convênios (com o aviso da recepção), especialidades, médicos e as avaliações. Unidade desativada
  ou de clínica bloqueada → 404.
- **Nota do local** = média das avaliações do local (`locais.media_avaliacoes`, recalculada pelo
  model `Avaliacao`).
- A posição do usuário **não é salva**: vai só na URL da busca, arredondada (~100 m). O CEP
  digitado vira `lat`/`lng` arredondados num redirecionamento. Nos testes a internet fica desligada
  (`LOCALIZACAO_EXTERNA=false` no `phpunit.xml`).

### 5.7 Testes automáticos

`php artisan test` → **173 testes** em `tests/Feature/`: cadastros, carteirinhas, busca e
distância, CEP com raio, avaliações, clínica (médicos, unidades, convênios), admin, exclusão de
conta, segurança, "Esqueci minha senha", travas do banco e as telas. Rodam no banco
`facilmed_testes` (criado sozinho), **nunca** no `facilmed`. Os de foto precisam da extensão
**GD** ligada no `php.ini` do XAMPP. Toda mudança de back-end vem com teste.

---

## 6. Estado atual e próximos passos

> Esta seção é a "passagem de bastão" entre quem trabalha no projeto (pessoas e IAs).
> **Atualize ao terminar cada etapa.**

**Atualizado em 13/10/2026.**

**Pendências até a entrega (12/10, depois da revisão geral):**
1. **⚠ Site no ar sem senha de demonstração.** O log do boot de 12/10 diz que a `DEMO_PASSWORD` está
   vazia no Render: as 9 contas do seed (admin, 6 clínicas, Ana, Marcos) ficaram com senha aleatória e
   ninguém entra nelas; o `ADMIN_EMAIL` também está vazio (não há admin extra). Criar a `DEMO_PASSWORD`
   no painel do Render (Environment) e publicar de novo (aba Actions → **Run workflow**). Conferir
   também `BREVO_API_KEY` e `MAIL_FROM_ADDRESS` (sem eles o "Esqueci minha senha" só vai para o log).
2. *(Feito em 13/10: rodapé, carrossel, admin com três telas de contas, galeria e site da unidade —
   ver a entrada de 13/10 abaixo.)* Ficou de fora só a barra Início/Perfil no celular. De 16/10 em
   diante não se constrói nada novo.
3. **Branches:** todas as do GitHub já estão na `main` ou foram superadas (`front/home-nova` foi
   absorvida pela home atual; `claude/compassionate-cori-dnjhvu` pela `integracao/pointmed`;
   `setup/ambiente-laravel` é de 20/09). Podem ser apagadas depois de o grupo concordar.
4. Ensaio da apresentação com as contas do §3 (16–20/10).

**13/10 — admin com três telas de contas, galeria e site da unidade, rodapé e carrossel** (feito com o
Claude a partir da `main` `4920abc`, branch `revisao/13-10-telas`; pedido do Sidney: itens 1, 3 e 4 da
lista de 12/10). Antes de começar: o zip que o Sidney mandou (`PointMed-main.zip`) é igual à `main`, e
não havia commit nem branch nova no GitHub.
- **Admin: Contas ativas, Contas bloqueadas e Contas excluídas** (decisão do Sidney de 01/10). O item
  "Usuários" do menu virou três (`admin.usuarios`, `admin.contas-bloqueadas`,
  `admin.contas-excluidas`; URLs `/admin/usuarios`, `/admin/usuarios/bloqueadas`,
  `/admin/usuarios/excluidas`). A mesma view (`admin/usuarios`) serve as três, com abas no topo e o
  total de cada; cada tela só tem a ação que cabe a ela (bloquear / desbloquear / nenhuma). Os nomes
  das rotas novas não começam com `admin.usuarios` de propósito: o menu acende o item com
  `routeIs('admin.usuarios*')`. Link antigo `?status=bloqueado|inativo` redireciona para a tela certa.
  Bloquear e desbloquear dizem para qual tela a conta foi. **Achado no caminho:** `?busca[]=x` na URL
  dava erro 500 nessa tela (o valor ia direto para o campo); corrigido. Teste novo `ContasAdminTest` (7).
- **Galeria de fotos e site da unidade.** Migration nova `2026_10_13_000100_galeria_e_site_dos_locais`:
  `locais.site` e a tabela `local_fotos` (a imagem fica na tabela `fotos`, como a foto de perfil; aqui só
  a referência). Em Clínica → Unidades, cada unidade tem o quadro "Fotos (n de 6)" com miniaturas,
  "Enviar foto" e o "x" para remover (`Clinica\FotoDaUnidadeController`, rotas
  `clinica.unidades.fotos.salvar`/`.remover`, 10 envios por minuto, `scopeBindings` para a foto ser
  daquela unidade, `lockForUpdate` para duas abas não passarem de 6). O campo **Site** entrou em
  "Editar dados" e em "Nova unidade" (`www.x.com.br` vira `https://www.x.com.br`; só http/https). Na
  página do local: galeria no topo (Alpine troca a foto pelas miniaturas) e o botão **Visitar o site**
  (outra aba, `rel="noopener noreferrer nofollow"`). A home ("Hospitais e Clínicas") usa a 1ª foto da
  galeria. As 6 unidades de demonstração ganham a foto da vitrine como 1ª foto — no seeder e também na
  migration, porque no site no ar o seeder não roda. Teste novo `GaleriaUnidadeTest` (11).
- **Rodapé do site:** saiu "Médicos, clínicas e convênios são fictícios" (pendência das notas de 05/10).
  Ficou "Projeto acadêmico (TCC) — Etec ... Sem contrato com nenhuma operadora de saúde.", que é o que o
  AGENTS §3 exige. Os avisos de convênio fictício dentro dos painéis (admin, clínica, Meu plano)
  continuam. Teste em `PaginasTest`.
- **Carrossel da home:** as 9 fotos ficaram (o grupo escolheu). Três legendas trocadas para combinar com
  a foto: si2 "Encontre quem atende perto de você", si4 "Veja os hospitais da sua cidade", si8
  "Pediatria e outras especialidades".
- **Conferido:** `migrate:fresh --seed` limpo; a migration nova aplicada também num banco que já tinha
  dados (as 6 unidades ganharam a foto); **173 testes** passando; telas novas abertas no navegador
  (computador e celular), sem erro de JavaScript nem nada passando da largura.
- **Depois de puxar:** `php artisan migrate` (ou `composer run banco-do-zero`) e Ctrl + F5.

**12/10 (noite) — revisão geral: textos, cartões e limpeza** (feito com o Claude, branch
`revisao/12-10-pendencias`, a partir da `main` `4a6f137`; pedido do Sidney: "olha todo o projeto e vê
o que falta"). Antes de mexer: `migrate:fresh --seed` limpo, 152 testes passando e ~250 páginas abertas
no navegador com as 4 visões (visitante, usuário, clínica, admin), no computador e no celular — nenhum
erro 500, nada passando da largura no celular, nenhuma imagem quebrada. No site no ar, nenhum erro 500
na última semana.
- **Home:** o contador "médicos verificados" virou **"médicos com CRM conferido"** — era o único texto
  público que dava a entender uma verificação de verdade (AGENTS §3).
- **Início do admin:** o cartão "Usuários cadastrados" mostrava só "cadastros no mês x mês anterior",
  sem número, quando ninguém se cadastrou no mês anterior (na demonstração é sempre assim). Agora mostra
  "2 cadastros neste mês"; com cadastro no mês anterior, continua a porcentagem. 2 testes novos no
  `TelasAdminTest`.
- **Início da clínica:** no cartão "Unidades" o nome da unidade ficava espremido numa coluna de 84 px
  e a etiqueta da nota ocupava o resto. A regra `.fm-linha--nota` estava só no `crud.css`, que essa
  tela não carrega; foi também para o `painel.css`.
- **Seed:** a descrição da Aurora dizia "agenda estendida" (sobra do agendamento) → "atendimento em
  horário estendido". Mensagens do boot do Render: "FacilMed:" → "PointMed:" (`entrypoint.sh`,
  `facilmed:garantir-admin`).
- **Arquivos sem uso apagados:** `public/css/agendamento.css`, `public/imgs/teste.jpg` e
  `public/imgs/sliderhospcli/` (1,1 MB; a home usa `public/imgs/inst`).
- **README:** esta lista de pendências; saíram do fim desta seção os blocos de 28–30/09 que descreviam a
  versão com agendamento; §2.7 com o nome novo do repositório (`cilugo/PointMed`); §10 com o cronograma
  real. Um teste com nome antigo ("comentário só para o autor…") foi renomeado.
- **Conferido:** `migrate:fresh --seed` limpo, **154 testes** passando e as telas mexidas abertas no
  navegador (computador e celular).
- **Depois de puxar:** `composer run banco-do-zero` (muda a descrição da Aurora no seed) e Ctrl + F5.

**Antes, em 12/10:**

**12/10 — segurança: revisão completa** (feito com o Claude, branch `seguranca/revisao-completa`, a
partir de `seguranca/vinculo-medico-cpf`; pedido do Sidney: "fazer tudo de uma vez e revisar tudo").
Cada item tem teste em `RevisaoSegurancaTest` (11 testes novos).
- **Link do "Esqueci minha senha" desviado (achado nesta revisão, o mais grave do bloco).** O
  `trustProxies(at: '*')` aceitava o cabeçalho `X-Forwarded-Host` de qualquer pessoa, e o Laravel
  montava o link do e-mail com ele: um golpista pedia a troca de senha da vítima com
  `X-Forwarded-Host: site-dele`, e o e-mail **de verdade** do PointMed chegava com o link (e o código
  secreto) apontando para o site dele. Corrigido em dois lugares: `bootstrap/app.php` confia só nos
  cabeçalhos de IP, porta e protocolo; e, no site no ar, `AppServiceProvider` força o endereço do
  `APP_URL`, em https, para todo link.
- **Trocar a senha derruba as outras sessões.** `AuthenticateSession` (do Laravel) no grupo `web`:
  quem estava logado com a senha antiga em outro aparelho (ou com "lembrar de mim") cai fora na
  próxima página; quem trocou continua. Vale também para o "Esqueci minha senha". Nos testes, o
  `TestCase::actingAs()` esquece a senha da conta anterior ao trocar de usuário (ver o comentário lá).
- **Limites de tentativas** (`throttle`, por usuário logado ou por endereço IP):
  cadastro de usuário e de clínica 10/min; "Esqueci minha senha" e "Nova senha" 6/min; trocar senha e
  excluir conta 6/min; avaliações 20/min; foto 10/min; especialidade nova da clínica 10/min (e, do
  item 2, cadastrar médico 10/min). Passou do limite, a resposta é 429 ("Muitas tentativas").
- **Busca por CEP:** a resposta do ViaCEP/Nominatim fica no cache 1 dia (CEP não encontrado, 1 hora),
  com a chave em hash — o que fica é "este CEP fica neste ponto", sem ligação com quem buscou. CEP novo
  conta no limite de 20 por minuto por IP; passou, a tela pede para esperar sem sair para a internet.
- **Localização fora do log:** o log do Apache no Render guardava a URL inteira, com o CEP e a
  latitude/longitude de quem buscou (conferido no log de 12/10). O `Dockerfile` troca o formato do log:
  sem a parte depois do `?` e sem o Referer. As mensagens de "ViaCEP/Nominatim fora do ar" também não
  levam mais a URL.
- **Fotos sem metadados:** `FotoDePerfil` redesenha a imagem com o GD antes de guardar — sai o EXIF
  (GPS, aparelho, data), a rotação do celular é aplicada antes (com teste), e a foto fica com no máximo 1024 px.
  O `Dockerfile` passou a instalar `gd` (com jpeg e webp) e `exif`. No XAMPP sem `extension=gd`, a
  foto é guardada como veio.
- **Cookie e cabeçalhos:** no site no ar o cookie da sessão só viaja por https
  (`config/session.php`, padrão ligado em `production`). Middleware novo `CabecalhosDeSeguranca`:
  `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy` e, no site no ar,
  `Strict-Transport-Security`. Sem CSP de propósito (o Alpine precisa avaliar as expressões das telas).
- **`entrypoint.sh`:** saiu o passo 4a (`migrate:fresh --seed` automático), que estava pendente.
- **Decidido não mexer** (fica registrado): o cadastro continua dizendo "Já existe um cadastro com
  esse CPF" — esconder isso exigiria confirmação por e-mail; o limite de 10 cadastros/min torna a
  consulta em massa inviável. A clínica continua criando especialidade sem aprovação do admin (com
  limite); se o grupo quiser moderação, é uma mudança de produto.
- **Limite que fica:** o Render põe o IP do visitante no começo do `X-Forwarded-For` e pode acrescentar
  IPs internos depois; o Laravel usa o último que não é do proxy. Se for um IP interno, os limites
  "por IP" passam a valer para o site inteiro (ex.: 10 cadastros por minuto somando todo mundo). Não
  abre brecha, só pode fazer alguém esperar um minuto. Os limites por usuário logado não mudam.
- **Conferido:** **152 testes** passando, `migrate:fresh --seed` limpo, os testes novos falhando no
  código antigo (o do link e o da foto) e o formato do log testado num Apache de verdade.

**12/10 — segurança: clínica não "pega" mais o médico de outra clínica** (revisão de segurança, item 2;
feito com o Claude, branch `seguranca/vinculo-medico-cpf`, a partir da `main` `9d936f0`; escolha:
CPF, porque o nome também aparece na página pública).
- **O problema:** em "Cadastrar médico", um médico que já existe era vinculado só pelo CRM/UF — e o
  CRM/UF aparece na página pública do médico, na busca e na página do local. Qualquer clínica (inclusive
  uma recém-cadastrada) vinculava a Dra. Helena a uma unidade dela e, pela `MedicoPolicy`, passava a
  editar foto, apresentação, especialidades e convênios dela, em **todas** as clínicas.
- **O que mudou:** o **CPF do médico** passou a ser obrigatório sempre, e para médico que já existe ele
  precisa ser o dele (`CadastrarMedicoPelaClinicaRequest::after()`, comparado só com dígitos e com
  `hash_equals`). O CPF é conferido antes da situação do CRM, então quem erra o CPF não descobre se o
  médico está rejeitado. Na tela, o CPF subiu para o bloco "CRM, CPF e unidade". O CPF não aparece em
  nenhuma tela pública.
- **Limite de tentativas:** `POST /clinica/medicos` aceita 10 envios por minuto por clínica
  (`throttle:10,1`), para ninguém ficar chutando CPF.
- **O que não muda:** vínculos que já existem continuam; quem já editava continua editando. Os CPFs dos
  8 médicos do seed estão no Git (`DadosFicticios`), então os médicos **de demonstração** continuam
  vinculáveis por quem ler o código — o que importa é o médico de verdade cadastrado pelo site.
- **Testes:** novo `VinculoMedicoTest` (6); o `ClinicaTest` passou a mandar o CPF do Dr. Rafael.
  **141 testes** passando, `migrate:fresh --seed` limpo.

**12/10 — segurança: a senha do README não abre mais o site no ar** (revisão de segurança feita com
o Claude; branch `seguranca/senha-demonstracao`, a partir da `main` `4c49fab`).
- **O problema:** o repositório é público, a §3 mostra `facilmed2026`, e o `entrypoint.sh` roda o
  seeder no Render. Qualquer pessoa entrava em facilmed.onrender.com como **admin** (com acesso ao e-mail de
  todo mundo que se cadastrou, bloqueava contas, mexia em convênios) ou como qualquer clínica do seed.
- **O que mudou:** variável nova **`DEMO_PASSWORD`** no Render (`render.yaml`, `config/app.php` →
  `app.senha_demonstracao`). Os seeders gravam `DadosFicticios::senha()`: a variável no site, uma
  senha aleatória no site sem a variável, e `facilmed2026` no XAMPP e nos testes. O `AdminSeeder`
  tinha `Hash::make('facilmed2026')` fixo e passou a usar o mesmo. Nenhum seeder escreve mais a senha
  do site no log.
- **Comando novo `facilmed:proteger-demonstracao`**, no `entrypoint.sh` depois do seeder (passo 5b):
  só age com `APP_ENV=production`; passa as 9 contas de demonstração
  (`DadosFicticios::emailsDasContas()`) para a `DEMO_PASSWORD` e apaga as sessões abertas, o
  "lembrar de mim" e os links de troca de senha dessas contas — quem entrou com a senha antiga cai
  fora. Não mexe em conta criada pelo site nem no admin de `ADMIN_EMAIL`. Está **sem `|| true`** de
  propósito: se falhar, o servidor não sobe com o admin aberto.
- **Testes:** novo `SenhaDemonstracaoTest` (7). Conferido aqui: `migrate:fresh --seed` limpo,
  **135 testes** passando e o boot do Render simulado (banco com a senha antiga → 9 contas trocadas;
  segundo boot → 0 trocadas; sem a variável → senha aleatória e aviso no log).
- **⚠ Antes de publicar:** criar `DEMO_PASSWORD` no Render (Environment). Sem ela, ninguém entra nas
  contas de demonstração (nem o grupo) até a variável ser preenchida.
- O resto da revisão foi feito no mesmo dia (bloco "revisão completa", acima).

**12/10 — unidade: editar e desativar; README revisado** (feito com o Claude a partir da `main`
`e756f1c`; escolhas do Sidney: ao desativar, os médicos ficam ligados à unidade; pode desativar a
última unidade, com aviso).
- **Clínica → Unidades ganhou "Editar dados" e "Desativar/Reativar unidade".** Antes não dava para
  corrigir endereço nem tirar uma unidade do ar, e a mensagem do horário mandava "desativar a
  unidade" sem existir o botão. Rotas `clinica.unidades.atualizar` (PUT) e `clinica.unidades.status`
  (PATCH, `ativo` 0/1), `AtualizarUnidadeRequest` (herda as regras do cadastro, autoriza pela
  `LocalPolicy`), campos em `clinica/parciais/unidade-campos` (o "Nova unidade" usa o mesmo).
- **Desativada:** some da busca, da home e da página pública (as regras já olhavam `locais.ativo`)
  e não recebe médico novo. Os vínculos não mudam; em "Meus médicos" aparece a etiqueta "Unidade
  desativada". Ao reativar, tudo volta. Sem nenhuma unidade ativa, a mensagem avisa que a clínica
  saiu da busca.
- **Endereço mudou → coordenada refeita:** `Local::booted` agora olha todos os campos do endereço
  (`Local::CAMPOS_DO_ENDERECO`), e a edição chama o `Geocodificador` como o cadastro.
- **Celular:** o campo "Nome da unidade" usava `grid-column: span 2` direto na tela e, com uma
  coluna só, espremia o formulário "Nova unidade". Virou a classe `.fm-campo--duplo` (só a partir
  de 720 px) em `crud.css`.
- **Testes:** novo `UnidadeTest` (11). Conferido aqui: `migrate:fresh --seed` limpo e **127 testes**
  passando; telas abertas no navegador (computador e celular), sem erro.
- **README:** §2, §4, §5 e §7 reescritas para o sistema atual (sem consultas, lembretes, preços
  nem área do médico); decisões antigas da §9 marcadas como substituídas. §10 e §11 ficam como
  histórico.
- **Menu dos painéis: o símbolo do PointMed é o botão** (pedido do Sidney; escolhas: no celular e
  no computador; fechado mostra o símbolo e os ícones). Sempre do lado esquerdo.
  - **Computador:** clicar no símbolo recolhe o menu para uma faixa de 76 px (símbolo + ícones; o nome
    de cada item aparece ao passar o mouse). Clicar de novo abre (símbolo, nome "PointMed" e os nomes).
    A escolha fica guardada no navegador (`localStorage`) e vale nas outras páginas.
  - **Celular/tablet:** o símbolo fica no topo, no lugar do ☰ (o "PointMed" do meio do topo saiu).
    Clicar abre a gaveta; clicar no símbolo da gaveta, no fundo escuro ou Esc fecha. O "X" saiu.
  - Arquivos: `public/javas/menu.js` (novo, carregado antes do Alpine para o menu não "piscar"),
    `layouts/painel`, `components/sidebar`, `painel.css` (o recorte `.fm-logo-recorte` saiu).
    Imagens novas recortadas do `logo.png`, sem mudar o desenho: `imgs/marca/simbolo-icone.png`
    (só o símbolo; virou também o ícone da aba) e `imgs/marca/nome.png` (só o "PointMed").
  - O logo do painel levava para a home do site; agora ele abre/fecha o menu.
  - Teste novo em `PaginasTest` (os três painéis carregam o botão). **127 → 128 testes.**
- **Depois de puxar:** nada de banco (não tem migration nova). `php artisan optimize:clear` se a
  tela de Unidades ou o menu não mudarem; no navegador, Ctrl + F5 para pegar o CSS novo.
- *(Feito em 12/10, revisão de segurança: o passo 4a do `docker/entrypoint.sh`, que rodava
  `migrate:fresh --seed` se a tabela `consultas` existisse, saiu.)*


**07–08/10 — integração: a linha PointMed vira a principal** (decisão do grupo, comunicada pelo
Lucas: "a versão do zip é a atual; agora é um site de rastreio de clínica"). Branch
`integracao/pointmed`, feita com o Claude a partir de `reorganizacao-05-10`.
- **Como foi juntado:** merge da `main` com a estratégia `ours` (o histórico registra a junção, o
  conteúdo fica o do PointMed). Agenda, consultas, login de médico e comentário privado da `main`
  **não** voltam. Da `main` foram trazidos, um commit cada:
  1. **CNPJ da Clínica Ferr Inni** na base simulada (tabela do §3).
  2. **E-mail pelo Brevo** (`App\Mail\BrevoTransport`, `MAIL_MAILER=brevo` + `BREVO_API_KEY`) e
     "Esqueci minha senha" com a mesma resposta com ou sem conta; falha no envio vira aviso, não 500.
  3. **Admin com e-mail real no Render** (`facilmed:garantir-admin`, variáveis `ADMIN_EMAIL` e
     `ADMIN_PASSWORD`, chamado no `docker/entrypoint.sh`).
  4. **Busca por CEP com raio** (5/10/20 km — RF03/RN07): campo CEP na home e em `/locais`,
     `App\Support\Geocodificador` (ViaCEP + Nominatim, com reserva no bairro/cidade), a URL leva
     `lat`/`lng`/`cep_origem` arredondados (a posição não é guardada). Unidade nova e cadastro de
     clínica gravam a coordenada exata do endereço. Nos testes a internet fica desligada
     (`LOCALIZACAO_EXTERNA=false` no `phpunit.xml`).
- **Fotos de perfil no banco** (decisão do grupo): tabela `fotos` (migration
  `2026_10_07_000100_fotos_no_banco`). `users.foto`/`medicos.foto` guardam `foto:<chave>`; a rota
  pública `/foto/{chave}` devolve a imagem. Motivo: o Render apaga arquivo enviado a cada deploy.
  Fotos do seeder continuam arquivos em `public/imgs/medicos`.
- **Home — "Hospitais e Clínicas" do banco**, com as imagens de `public/imgs/inst`
  (`HomeController::IMAGENS_DA_VITRINE`, pelo nome do local). Saiu a lista fixa com o "Hospital Vale
  Sereno", que não existia. `esperaca.png` virou `esperanca.png`.
- **⚠ Render, banco zerado UMA vez (decisão do grupo, opção "a"):** o banco do Aiven estava no
  formato da versão com agendamento. O `entrypoint.sh` confere se a tabela `consultas` existe; se
  existe, roda `migrate:fresh --seed` e o site volta com os dados de demonstração. Contas criadas
  direto no site antigo (ex.: Clínica Ferr Inni) precisam ser criadas de novo. Depois disso o passo
  não faz nada. No painel do Render: conferir `APP_NAME=PointMed` e preencher `BREVO_API_KEY`,
  `MAIL_FROM_ADDRESS`, `ADMIN_EMAIL` e `ADMIN_PASSWORD` se quiserem e-mail e admin reais.
- **Testes:** novos `RecuperarSenhaTest` (trazido e adaptado) e `BuscaCepRaioTest`; fotos e home
  com testes novos em `ReorganizacaoTest`/`ClinicaTest`. Conferido em 08/10 no XAMPP do Lucas:
  **116 testes passando** (banco `facilmed_testes`, recriado com migrations + seed pelo
  `RefreshDatabase`). **Atenção:** os 5 testes de foto precisam da extensão **GD** do PHP, que vem
  desligada no `php.ini` do XAMPP (`;extension=gd`, linha ~931). Sem ela, 4 falham com "GD extension
  is not installed". Para ligar: tire o `;` da linha e reinicie o Apache.
- **Depois de puxar:** os mesmos três comandos (todo mundo precisa do `migrate:fresh`).
- **Ficou de fora (opcionais da main, não pedidos):** galeria de fotos e site do local, admin com
  três telas de contas, barra Início/Perfil no celular. Imagens de `inst` não foram comprimidas
  (~2,5 MB cada).

**05/10 — PointMed, "usuário", faixa da clínica e comentário público** (PDF "Notas sobre FacilMed",
do Lucas; decisões do grupo). Feito com o Claude numa cópia do projeto, conferido em 06/10; branch
`reorganizacao-05-10`, para o grupo revisar no GitHub Desktop.
- **Nome: FacilMed → PointMed** (existe o app "FácilMED", de telemedicina). Trocados todos os textos
  de tela, os títulos e os 5 logos de `public/imgs/marca/` (mesmo símbolo, "PointMed" na fonte
  Rosario). **Ficam de propósito:** pasta `FacilMed`, URL `localhost/FacilMed`, banco `facilmed`,
  e-mails `@facilmed.test` e comandos `facilmed:` — mudar quebraria a máquina de todo mundo.
  **Cada um troca `APP_NAME=PointMed` no próprio `.env`** (o `.env` não vai no Git).
- **"Paciente" → "usuário"** em textos, código e banco. Migration
  `2026_10_05_000100_usuario_e_faixa_da_clinica`: `pacientes`→`usuarios`,
  `paciente_planos`→`usuario_planos`, `paciente_id`→`usuario_id` e `users.tipo` 'paciente'→'usuario'.
  Classes `Usuario`, `UsuarioPlano`, `Controllers\Usuario\*`, views `usuario/*`, rotas `/usuario/...`
  (`usuario.*`), middleware `tipo:usuario`, `User::TIPO_USUARIO`, `ehUsuario()`. **Atenção:** `users` é
  a conta de acesso de todos; `usuarios` é o perfil de quem busca. Os nomes antigos de índice
  (`uq_avaliacao_paciente_local`…) ficaram: o MariaDB do XAMPP não tem `RENAME INDEX`.
- **Tabela de preços saiu; a clínica escolhe a faixa de cada unidade.** `locais.faixa_preco` (1 a 4
  ou vazio, CHECK `chk_local_faixa`), escolhida em Clínica → Unidades (rota `clinica.unidades.faixa`).
  A migration calcula a faixa inicial a partir dos preços antigos. Saíram a tabela `precos`, `Preco`,
  `PrecoController`, a tela e `App\Support\Dinheiro` (só servia para ler o preço digitado). A
  especialidade oferecida num local passou a ser a especialidade **ativa** do médico
  (`Vinculo::scopeOferece`). O painel da clínica avisa "unidades sem faixa".
- **Comentário das avaliações é PÚBLICO.** As páginas do local e do médico mostram as 10 mais
  recentes (`publico/parciais/avaliacoes`); o autor aparece como "Ana L." (`Formatador::nomeCurto`),
  sem foto. O card "Médicos bem avaliados" da home mostra o comentário mais recente.
- **Outros itens das notas:** "Acesso rápido" saiu dos painéis do usuário e da clínica (e o
  `Formatador::atalhos`, que só servia a ele); 3º cartão "Clínicas e hospitais" no painel do usuário;
  legendas clichê do carrossel da home trocadas (as imagens ficaram — **o grupo decide** se tira).
- **Ajustes de tela (06/10):** o logo do menu lateral dos painéis aparecia cortado ("PointMec"),
  porque o recorte em `painel.css` (`.fm-logo-recorte`) tinha a largura do "FacilMed"; agora usa a do
  "PointMed" (987 x 179). Os cartões do painel do usuário ocupam a largura toda (uma coluna por
  cartão, `--fm-cartoes`), sem o buraco à direita que o Lucas apontou.
- **Conferido no navegador (06/10):** home, página da Vida Plena (visitante e Ana, computador e
  celular), painéis do usuário, da clínica e do admin, e Clínica → Unidades com o seletor de faixa.
  Nenhum "FacilMed", "paciente", "Acesso rápido" ou "Tabela de preços" na tela; nenhum erro 500 ou de
  JavaScript.
- **Testes:** os que testavam o comportamento antigo foram adaptados (comentário agora aparece só com
  o nome curto; médico aparece na busca com especialidade ativa; painel sem atalhos). Conferido em
  06/10: `migrate:fresh --seed` limpo e **102 testes** passando.
- **Depois de puxar:** `composer dump-autoload`, `php artisan migrate:fresh --seed` e trocar
  `APP_NAME=PointMed` no `.env`.
- **Fica para perto da apresentação:** tirar as menções a "dados fictícios" das telas (6 views).
- **Atenção — a `main` do GitHub andou em outra direção em 01/10** (branch `claude/pivo-rastreador`:
  agendamento continua, médico com login, comentário privado, fotos no banco, busca por CEP com raio,
  e-mail pelo Brevo). Esta linha de trabalho parte do `e87d8a2`, **antes** daquilo. O grupo precisa
  decidir qual linha vale antes de juntar na `main` — juntar sem decidir dá conflito em quase tudo.
- **Próximo passo sugerido:** os slides da defesa prometem busca por **CEP** (RF03) e raio de
  **20 km** (RN07). Aqui a busca usa a localização do aparelho ou a cidade; o CEP com raio existe só
  na `main` e pode ser trazido de lá.

**01/10 — reorganização: o FacilMed deixa de agendar** (documento "Modificações - 01/10" do grupo;
decisões do grupo: tirar o agendamento por completo e trocar o valor por faixa de preço). Feito com
o Claude numa cópia do projeto; o grupo revisa no GitHub Desktop antes do commit.
- **Banco:** migration nova `2026_10_01_000100_reorganizacao_sem_consultas` (não tem `down`; para
  voltar, Git + `migrate:fresh --seed`). Saem `consultas`, `notificacoes_enviadas`,
  `disponibilidades`, `bloqueios`, `feriados`, `feriado_local` e `paciente_acessibilidade`. O médico
  ganha `medicos.nome` e perde `user_id` e `senha_temporaria`; as contas de médico são apagadas e
  `users.tipo` só aceita paciente, clinica e admin. `locais.medico_id` sai (todo local é de uma
  clínica) e locais ganham `media_avaliacoes`/`total_avaliacoes`. `avaliacoes` é recriada (local OU
  médico, uma por paciente em cada, CHECKs e UNIQUEs). `users.foto` para a foto de perfil.
- **Itens do documento:** (1) "Médicos bem avaliados" mostrava 3 porque o banco tinha 3 — agora são
  8 médicos no seed, com foto; (2) área do médico removida; (3) "Conferir carteirinhas" saiu e
  "Verificar CRM" virou **Verificar CNPJ** (`Admin\CnpjController`); (4) a clínica cria
  especialidade (`Clinica\EspecialidadeController`, regra do slug em `Especialidade::criar`) e edita
  o perfil do médico (`Clinica\MedicoController@editar/atualizar`, `MedicoPolicy`); (5)(6) painéis do
  admin, da clínica e do paciente refeitos sem consultas; (7) filtro de convênio em `/locais`;
  (8) médico sem conta; (9) **faixa de preço** por `App\Support\FaixaDePreco` ($ até R$ 200, $$ até
  R$ 350, $$$ até R$ 500, $$$$ acima — média dos valores) e menu do site diferente para quem entrou;
  (10) "Minhas consultas" saiu com o agendamento; (11) foto de perfil para paciente, clínica e admin
  (`FotoController`, `App\Support\FotoDePerfil`, em `public/uploads/fotos`, fora do Git).
- **Avaliação:** sem consulta, o paciente avalia o local (página do local) ou o médico (perfil dele);
  avaliar de novo edita. Comentário: autor, clínica e admin. Tela "Minhas avaliações" no painel.
- **Decisões do Claude para o grupo revisar:** qualquer clínica onde o médico atende pode editar o
  perfil dele; nome/CRM/UF não mudam por tela; consultório próprio de médico saiu; acessibilidade
  saiu (sem finalidade, LGPD); a exclusão de conta agora apaga carteirinhas, foto e comentários.
- **Testes:** os de agendamento, e-mail, área do médico e acessibilidade foram apagados; os outros
  adaptados; novos `AvaliacaoTest` e `ReorganizacaoTest` e 7 no `ClinicaTest`. Conferido:
  `migrate:fresh --seed` limpo e **110 testes** passando. Os testes de foto precisam da extensão GD
  (o XAMPP já traz).
- **Depois de puxar:** `composer dump-autoload` e `php artisan migrate:fresh --seed`.
- **Desatualizado neste README (fica para a próxima revisão):** §5.3 (horário livre), §5.5 (e-mails)
  e §7.1 (telas do médico) descrevem o sistema de antes de 01/10.

**30/09 — exclusão de conta pelo paciente (LGPD)** (branch `back/exclusao-de-conta`; último item
decidido do plano do app; escolhas do Sidney: só paciente, anonimizar em vez de apagar, a nota fica
e o comentário sai):
- **Onde:** "Meu perfil" → bloco "Excluir minha conta", no fim da página. Pede a senha atual e a caixa
  "entendo que não pode ser desfeita" (`App\Http\Requests\Paciente\ExcluirContaRequest`, com os erros
  no "saco" próprio `excluirConta`, para não se misturarem com os do formulário de senha). Rota
  `DELETE /paciente/perfil` (`paciente.perfil.excluir`).
- **A regra inteira** está em `Paciente::excluirConta()`, numa transação, com a linha do paciente
  travada (`lockForUpdate`, como no agendamento):
  consultas futuras canceladas por `Consulta::cancelar()` (o médico recebe o aviso de sempre, e nele o
  paciente aparece como "Conta excluída"); o texto livre que o paciente escreveu nas consultas
  (observações e o motivo quando ele mesmo cancelou) apagado; acessibilidade apagada; carteirinhas
  apagadas, **menos as já usadas em consulta**, que ficam sem número nem titular (é a mesma regra do
  `PlanoController::remover`, e os números "por convênio" da clínica dependem delas); avaliações com a
  nota mantida e o comentário apagado (a média do médico não muda); o e-mail antigo sai de
  `notificacoes_enviadas` e de `password_reset_tokens`; as sessões abertas (IP e navegador) são
  apagadas; nome vira "Conta excluída", e-mail vira `excluida-{id}@facilmed.invalid` (o domínio
  `.invalid` é reservado e nunca entrega nada), telefone, CPF, nascimento e sexo ficam vazios, a senha
  vira uma aleatória e o status vira `inativo` — o `GarantirContaAtiva` já barra.
- **Banco:** migration nova `2026_09_30_000100_exclusao_de_conta` — `users.excluida_em` (quando o pedido
  foi atendido), o CHECK `chk_users_excluida_inativa` (conta excluída nunca volta a `ativo`, nem por um
  UPDATE na mão) e `pacientes.cpf` aceitando vazio. O índice único do CPF continua (o `->change()` não
  mexe em índice); por isso a pessoa pode se cadastrar de novo com o mesmo e-mail e CPF.
- **Agendamento:** depois de travar a linha do paciente, o `AgendamentoController::salvar` confere de
  novo se a conta está ativa. Sem isso, um agendamento feito em outra aba no mesmo segundo da exclusão
  passava pelo middleware antes e gravava uma consulta para a conta já excluída.
- **Senha na sessão:** o campo de senha da exclusão se chama `current_password` (e não "senha_atual")
  porque é um dos nomes que o Laravel nunca guarda na sessão quando a validação falha.
- **Admin:** a lista de usuários mostra "Excluída" e "Excluída pelo próprio paciente em ...", sem o botão
  de bloquear. `UsuarioController::bloquear` recusa conta excluída (bloquear e depois desbloquear a
  colocaria de volta como ativa). No dashboard do admin, conta excluída não entra em "Pacientes
  cadastrados", nos cadastros do mês nem em "Últimos cadastros".
- **Revisão independente** (um agente que não viu o código sendo escrito): achou as observações, o motivo
  de cancelamento, a corrida com o agendamento, a senha na sessão, o CHECK e as carteirinhas usadas —
  tudo corrigido acima, com teste. **Limites que ficam:** uma avaliação enviada no mesmo segundo da
  exclusão, em outra aba, pode ficar com o comentário (a janela é de milissegundos; travar a avaliação
  não compensa); e, em desenvolvimento, o `storage/logs/laravel.log` guarda os e-mails "enviados" com
  nome e endereço (é arquivo local da máquina de cada um, fora do Git).
- 16 testes novos no `ExclusaoDeContaTest` — os que usam consulta passada pegam uma consulta pelo status e a
  passam para a Ana, porque o `ConsultaSeeder` sorteia o paciente. Conferido: **170 testes** (várias vezes,
  com sorteios diferentes), `migrate:fresh --seed` limpo, a migration desfeita e aplicada de novo num
  banco que já tinha dados (índice único mantido, CPFs intactos, CHECK barrando) e, no
  navegador, o caminho inteiro: senha errada, exclusão, aviso no login, tentar entrar de novo ("E-mail
  ou senha incorretos", sem revelar que a conta existiu) e o bloco no celular.

**30/09 — login com as bolinhas do fundo maiores** (pedido do Sidney; branch `front/login-bolinhas`):
em `public/css/login.css`, as três passaram de 420/260/180 px para 540/340/240 px. Em tela baixa (até
760 px de altura, notebook pequeno) a de cima e a de baixo ficam num tamanho no meio, para não
encostarem na logo nem no slogan. As bolinhas do **cadastro** não mudaram (o Lucas tinha acabado de
deixá-las com as medidas antigas do login, em `06a32de`): se o grupo quiser as duas telas iguais de
novo, é copiar os mesmos números para `.circulo--1/2/3` do `cadastro.css`.

**30/09 — como o trabalho do Claude chega no site (decisão do Sidney):** cada assunto continua numa
branch própria (fica o histórico), mas é juntado na `main` e enviado na hora, sem esperar revisão —
cada push na `main` já publica no Render (§2.7). Quem quiser revisar, olha a branch ou o merge no
histórico.

**30/09 — commits do grupo direto na `main`:** `06a32de` (painel da esquerda do cadastro com as
medidas do login) e `90a1e46` (sai o "Acesso rápido" do dashboard do admin). Nenhum teste dependia
disso. O `Admin\DashboardController` ainda mandava `$atalhos`, que ficou sem uso, e saiu.

**29/09 (noite) — Locais perto de você e página do local** (plano do app; escolhas do Sidney em
29/09: tela nova de locais, localização do navegador ou cidade, botão na home):
- **`/locais`** — clínicas, hospitais e consultórios do mais perto para o mais longe, com filtro de
  especialidade, nota do local, preço "a partir de" e se aceita convênio. A busca de médicos
  (`/buscar`) continua igual, com um link para cá.
- **`/local/{id}`** — a página do local (§5.6), com os médicos disponíveis e "Ver horários".
- **"Usar minha localização"** (`public/javas/localizacao.js`) na tela de locais e embaixo da busca da
  home: o navegador pergunta se a pessoa permite; se ela negar, aparece o aviso para escolher a cidade.
  Na home foi só acrescentado o botão, sem mexer no resto (OK do Sidney).
- **Banco:** migration nova `2026_09_29_000100_add_coordenadas_to_locais_table` (latitude e longitude
  aproximadas, nulas se a cidade não estiver na lista). Ela mesma preenche os locais que já existem —
  inclusive os do site no ar, que não roda o seeder de novo.
- **Menu:** item novo "Perto de você". Com 7 itens, o menu vira o botão ☰ abaixo de 1300 px (era 1160).
- Links novos: o nome de cada unidade (página da clínica) e de cada lugar (página do médico) abre a
  página do local. Na página do médico, consultório próprio aparecia como "Clínica"; agora "Consultório".
- `BuscaController`: o filtro de texto da URL virou o método `texto()`, usado pelas duas buscas.
- 13 testes novos no `LocaisTest`. Conferido: **154 testes**, `migrate:fresh --seed` limpo, a migration
  rodada também num banco que já tinha dados, e no navegador: permitir a localização (Taubaté → Santa
  Clara a 600 m primeiro), negar (aparece o aviso), computador e celular.

**29/09 (tarde) — paciente em dois lugares e tela de escolha do cadastro:**
- **O mesmo paciente não marca mais duas consultas no mesmo horário** (era o primeiro item do "Para o
  grupo olhar"; OK do Sidney em 29/09). Vale para qualquer sobreposição, não só o mesmo minuto: uma
  consulta de 60 min às 9h barra outra às 9h30; uma que termina 9h30 não barra outra às 9h30.
  - Regra em `Paciente::consultaNoHorario()` (model). O `AgendamentoController` chama em dois lugares:
    `confirmar()` volta para a tela de horários com "Você já tem uma consulta nesse horário: 02/10 às
    09:00, com Dra. Helena Navarro, em Vida Plena - Centro. Escolha outro horário."; `salvar()` confere
    de novo dentro da transação.
  - Em `salvar()`, `lockForUpdate()` na linha do paciente: se ele mandar dois agendamentos no mesmo
    segundo (duas abas), o segundo espera o primeiro gravar e aí enxerga o conflito. O índice único do
    banco não cobre isso porque é por médico. **Sem migration nova.**
  - Na remarcação, a consulta antiga não conta (ela é cancelada na mesma operação).
  - `ConsultaSeeder`: o paciente continua sorteado, mas o horário agora é o primeiro livre para o
    médico **e** para o paciente (`horarioLivre()`). Continuam 22 consultas. Como o horário depende do
    que já está marcado, ele **não roda de novo** num banco que já tem consultas (os outros seeders
    continuam podendo rodar de novo) — para recriar, `composer run banco-do-zero`.
  - 8 testes novos no `AgendamentoTest` (mesmo horário, horário no meio da outra, logo depois pode,
    outro paciente pode, cancelada libera, a confirmação avisa, remarcação, e os dados de demonstração
    sem paciente em dois lugares).
- **Tela "Como você deseja se cadastrar?" com o visual que o grupo fez** (`style1.css`, conversa do
  grupo com o Claude em 29/09, "medidas tiradas da tela de login"): título maior, botões de 54 px,
  círculos em `vh` (não se encostam em tela grande), logo um pouco abaixo do centro. Fica em
  `public/css/cadastro.css`, bloco `.pagina--escolha`, que só vale nessa tela (o layout ganhou
  `@section('pagina_classe')`). Paciente e clínica continuam com o visual da Mari. Cores: as da marca
  (§8) no lugar dos tons aproximados do `style1.css`. Botões com "Sou Paciente" / "Sou Clínica/Hospital",
  como no protótipo.
- O commit "login e cadastro" (`900ed0c`, 29/09) mexeu só no `prototipo-antigo/`; o sistema em Laravel
  já tinha o que ele trouxe (escolha só com paciente e clínica, logo oficial com link para o início).
- Conferido: **141 testes** passando, `migrate:fresh --seed` limpo (5 vezes, sem paciente em dois
  lugares), e as telas de escolha, login e cadastro de paciente abertas no navegador (computador e celular).

**29/09 — `composer.lock` de volta:** o commit `cee1442` ("sla") apagou o `composer.lock` da `main`.
Foi confusão: a instrução era apagar só a cópia local, que travava o Pull (§2.6). Sem o arquivo, o
`COPY composer.json composer.lock` do `Dockerfile` falha e o Render para de atualizar. Ele voltou
idêntico ao do `3f8fdd4`. Conferido: `composer install` pelo lock (como no Render),
`migrate:fresh --seed` limpo e 133 testes passando.

**29/09 — site no ar (Render + Aiven):** `Dockerfile`, `docker/entrypoint.sh`, `render.yaml` e
`.dockerignore` para publicar pelo Render (passo a passo na §2.7). Única mudança no código:
`trustProxies(at: '*')` no `bootstrap/app.php`, para o Laravel gerar links `https` atrás do proxy
do Render (no XAMPP não muda nada). Conferido com MySQL 8 nas mesmas travas do Aiven (chave primária
obrigatória e conexão segura): 28 migrations e seed limpos, 2º boot não duplica dados, login dos 4
tipos de conta e todas as abas principais respondendo 200 com links em https.
**Atenção, testes no MySQL 8** (no MariaDB do XAMPP passam): `BancoTest::checks_do_banco` espera o
código `23000`, mas o MySQL 8 devolve `HY000` para CHECK violado (a trava funciona, só muda o
código); `MedicoTest::especialidades_com_principal...` depende da hora em que roda (a regra
"consulta futura marcada" barra a troca). Já falhavam antes do deploy.
- **Tela "419 PAGE EXPIRED" no login/cadastro (29/09, no site no ar):** acontecia ao enviar um
  formulário que ficou velho (voltar pelo navegador depois de se cadastrar, página aberta muito
  tempo, outra conta em outra aba). Agora o `bootstrap/app.php` devolve a pessoa para a mesma página
  com o aviso "A página ficou desatualizada..." e os campos preenchidos (menos a senha); na segunda
  tentativa entra. `layouts/auth` passou a mostrar `session('erro')`. Teste em `SegurancaTest`.
- **Publicação automática (29/09):** `.github/workflows/deploy-render.yml` + segredo
  `RENDER_DEPLOY_HOOK` no GitHub. Todo push na `main` publica no Render (§2.7).

**29/09 — cadastro (branch `front/cadastro`, feita em cima da `revisao/28-09`):**
- **Conferência antes de mexer:** subi a `revisao/28-09` com MariaDB e abri 209 páginas no navegador
  (todas as abas dos 4 painéis e os links de dentro delas, no computador e no celular). Nenhum erro
  500, e os cadastros funcionaram.
- **Médico não se cadastra mais sozinho** (plano do app de 28/09; decisão do Sidney em 29/09: fica
  paciente e clínica, como no protótipo da Mari). Ele entra por **Clínica → Meus médicos →
  Cadastrar médico**, que já conferia o CRM na base simulada. `/cadastro/medico` volta para `/cadastro`.
  Os testes de CRM recusado passaram a rodar por esse caminho.
- **Visual da Mari no cadastro** (protótipo `cadpac`, commit "Cadastros"): escolha, paciente e
  clínica em tela dividida, cartões com ícone. Arquivos: `layouts/cadastro.blade.php`,
  `public/css/cadastro.css`, `public/javas/cadastro.js` (máscaras e "mostrar senha"; validar continua
  no FormRequest), `cadastro/_campo`, `_icone`, `_avisos` e o componente `<x-cadastro.card>`
  (`components/cadastro/card.blade.php` — componente anônimo do Blade: o arquivo vira a tag).
  Diferenças do protótipo: logo oficial no lugar do `logo.svg`; ficam CPF, acessibilidade com
  consentimento e a primeira unidade da clínica; saem CNES, cargo e usuário (não existem no banco; o
  login é pelo e-mail). O `layouts/publico` ficou sem uso e saiu.
- **Corrigido:** pelo XAMPP (`/FacilMed`), `/register` e `/cadastro/medico` mandavam para
  `http://localhost/cadastro` (404) — §11, item 40.
- Conferido: **132 testes** passando, `migrate:fresh --seed` limpo e a varredura no navegador de novo
  (213 páginas, 0 erro), incluindo o caminho inteiro: clínica nova cadastra o médico → ele entra com
  a senha provisória → é obrigado a trocar → usa a agenda.

**Junção de 25/09/2026:** a home que o grupo fez no protótipo depois da organização (slider do início com 9 fotos, fotos dos médicos, "Hospitais e Clínicas" e "Sobre") foi trazida para `resources/views/home.blade.php`, `public/css/home.css` e `public/javas/home.js`; fotos em `public/imgs/` (sliderinicio, medicos, sliderhospcli — estas comprimidas de 10,5 MB para 1 MB). Os 3 médicos fictícios ganharam foto (`foto` em `DadosFicticios::MEDICOS`). O `prototipo-antigo/` também foi atualizado com a versão do GitHub (a home agora é `paginas/index.html`). **27/09:** entrou o commit `c9faffe` (mudanças na home: slider em loop contínuo, menu que marca o item clicado, cores em azul-escuro, novo texto do topo, "Sobre nós" e nova foto `medico5`), no protótipo e na home em Laravel.

*(12/10/2026: os blocos "Pronto e testado", "Telas internas — o que vale saber", "Próximos passos",
"Para o grupo olhar", "Plano do app", "Pendente de decisão" e "Não reabrir sem motivo" de 28–30/09 saíram
daqui: descreviam a versão com agendamento, consultas, preços e área do médico, que não existe mais
desde 01/10. Estão no histórico do Git. O que vale hoje está em "Pendências até a entrega", no topo
desta seção.)*

---

## 7. Mapa das telas internas

> **Reescrito em 12/10/2026.** A versão anterior descrevia as telas de antes de 01/10 (agenda,
> horários, preços e a área do médico), que não existem mais — está no histórico do Git.

Os menus de cada tipo de conta ficam em `config/navegacao.php` (o nome da rota é o contrato: se
renomear uma rota, ajuste lá). `tests/Feature/TelasInternasTest.php`, `TelasClinicaTest` e
`TelasAdminTest` abrem cada tela e conferem o que o controller entrega.

**Como fazer uma tela nova:** copie uma que já existe — `usuario/perfil.blade.php` (formulários) ou
`clinica/convenios.blade.php` (listas). Layout `layouts.painel`, classes `fm-*` de
`public/css/painel.css` e `crud.css`. `session('sucesso')` e `session('erro')` aparecem sozinhas no
layout. Todo formulário tem `@csrf` (PUT/PATCH/DELETE: `@method(...)`). Nada de regra de negócio na view.

### 7.1 Usuário (quem busca clínicas)

| Menu | Rota (GET) | Tela | Ações |
|---|---|---|---|
| Início | `/usuario` | `usuario/dashboard` | busca rápida de locais (especialidade, cidade, convênio do plano) |
| Perto de você | `/locais` | `busca/locais` | filtros; "Usar minha localização"; CEP com raio |
| Encontrar médicos | `/buscar` | `busca/index` | filtros e ordem (avaliação, faixa, nome) |
| Minhas avaliações | `/usuario/avaliacoes` | `usuario/avaliacoes` | DELETE `usuario.avaliacoes.excluir` |
| Meu plano | `/usuario/planos` | `usuario/planos` | POST `usuario.planos.salvar` (carteirinha, conferida na base simulada); DELETE `usuario.planos.remover` |
| Meu perfil | `/usuario/perfil` | `usuario/perfil` | PUT `usuario.perfil.atualizar`; foto (`foto.atualizar`/`foto.remover`); senha; DELETE `usuario.perfil.excluir` (excluir conta) |

Avaliar fica nas páginas públicas: POST `avaliacoes.local` (`/local/{id}/avaliar`) e
`avaliacoes.medico` (`/medico/{id}/avaliar`) — `estrelas` (1 a 5) e `comentario` (até 1000).

### 7.2 Clínica / hospital

| Menu | Rota (GET) | Tela | Ações |
|---|---|---|---|
| Início | `/clinica` | `clinica/dashboard` | cartões (médicos, unidades ativas, nota) e pendência "unidades sem faixa" |
| Meus médicos | `/clinica/medicos` | `clinica/medicos` | filtro por unidade; DELETE `clinica.medicos.desvincular`; médico em unidade desativada aparece com a etiqueta "Unidade desativada" |
| ↳ Cadastrar médico | `/clinica/medicos/novo` | `clinica/medicos-form` | POST `clinica.medicos.salvar` — `name`, `cpf`, `crm`, `uf`, `local_id` (unidade **ativa** da clínica), `especialidades[]`, `aceita_particular`, `aceita_convenio`. CRM já cadastrado só ganha o vínculo, e só com o `cpf` dele (12/10); 10 envios por minuto |
| ↳ Editar perfil | `/clinica/medicos/{id}/editar` | `clinica/medicos-editar` | PUT `clinica.medicos.atualizar` — `bio`, `anos_atuacao`, `telefone_profissional`, `especialidades[]`, `principal`, `convenios[]`, `foto`, `remover_foto` |
| Especialidades | `/clinica/especialidades` | `clinica/especialidades` | POST `clinica.especialidades.salvar` — `nome` |
| Unidades | `/clinica/unidades` | `clinica/unidades` | ver abaixo |
| Convênios | `/clinica/convenios` | `clinica/convenios` | POST `clinica.convenios.salvar` — `vinculo_id`, `aceita_convenio` |
| Avaliações | `/clinica/avaliacoes` | `clinica/avaliacoes` | só leitura (notas por unidade e por médico, com comentário) |
| Perfil da clínica | `/clinica/perfil` | `clinica/perfil` | PUT `clinica.perfil.atualizar` — `name`, `nome_fantasia`, `descricao`, `telefone` (CNPJ e razão social não mudam); foto; senha |

**Unidades** (`$locais` com `horarios` e `vinculos_count`; `$dias`):
- POST `clinica.unidades.salvar` — nova unidade: `nome`, `tipo` (clinica/hospital), `cep`, `endereco`,
  `numero`, `complemento`, `bairro`, `cidade`, `uf`, `telefone`, `faixa_preco`, `horarios[dia][abre|fecha]`.
- PUT `clinica.unidades.atualizar` *(12/10)* — os mesmos campos, menos faixa e horários
  (`AtualizarUnidadeRequest`). Os campos ficam em `clinica/parciais/unidade-campos`, usado pelos dois formulários.
- PATCH `clinica.unidades.status` *(12/10)* — `ativo` (0 ou 1). Desativar não mexe nos vínculos.
- PUT `clinica.unidades.faixa` — `faixa_preco` (vazio, ou 1 a 4).
- POST `clinica.unidades.fotos.salvar` *(13/10)* — `foto` (JPG/PNG/WEBP até 2 MB; até 6 por unidade).
  DELETE `clinica.unidades.fotos.remover` — `/clinica/unidades/{local}/fotos/{foto}`.
- O campo `site` *(13/10)* vai junto com os dados (nova unidade e "Editar dados"); só http/https.
- PUT `clinica.unidades.horarios` — `horarios[dia][abre|fecha]` (dia vazio = fechado; pelo menos um dia aberto).

As que mexem numa unidade existente passam pela `LocalPolicy`: só a clínica dona dela. A nova unidade
nasce sempre da clínica logada.

### 7.3 Admin

| Menu | Rota (GET) | Tela | Ações |
|---|---|---|---|
| Início | `/admin` | `admin/dashboard` | cartões da plataforma |
| Contas ativas *(13/10)* | `/admin/usuarios?tipo=&busca=` | `admin/usuarios` | POST `admin.usuarios.bloquear` — `motivo` (mín. 10; não aparece para admin) |
| Contas bloqueadas *(13/10)* | `/admin/usuarios/bloqueadas` (`admin.contas-bloqueadas`) | `admin/usuarios` | POST `admin.usuarios.desbloquear` |
| Contas excluídas *(13/10)* | `/admin/usuarios/excluidas` (`admin.contas-excluidas`) | `admin/usuarios` | só leitura (conta excluída não volta) |
| Verificar CNPJ | `/admin/cnpjs` | `admin/cnpjs` | só leitura: situação de cada CNPJ na base simulada |
| Clínicas e hospitais | `/admin/clinicas?busca=` | `admin/clinicas` | só leitura (clínica e as unidades dela) |
| Especialidades | `/admin/especialidades` | `admin/especialidades` | POST `admin.especialidades.salvar`; PUT `admin.especialidades.atualizar` (URL pelo **slug**) — `nome`, `icone`, `destaque`, `ativo` |
| Convênios | `/admin/convenios` | `admin/convenios` | criar/editar convênio e plano; PATCH `admin.convenios.status` e `admin.convenios.planos.status` (desativar — **nunca apagar**) |
| Meu perfil | `/admin/perfil` | `admin/perfil` | foto e senha |

---

## 8. Identidade visual e tom de voz

**Marca:** mão envolvendo um coração, em dois azuis. Slogan: *"Sua saúde, conectada."*
Arquivos em `public/imgs/marca/` (`logo-horizontal.png` no cabeçalho, `logocomslogan.png` no
login, `simbolo.png`/`logosemslogan.png` no rodapé e favicon). Nos painéis (12/10/2026):
`simbolo-icone.png` (só o símbolo, botão do menu e ícone da aba) e `nome.png` (só o "PointMed"),
recortados do `logo.png`.

**Cores:** marinho `#183E9F` (menu, títulos), azul-claro `#1C9CE5` (destaque), azul do coração
`#4A9BDA`. Cards brancos arredondados, fundo claro. Verde, roxo e rosa **só** para status —
nunca como cor de marca.

**Onde está o visual:**
- Site público → `public/css/home.css` (a home que o grupo fez) + `site.css` (no fim, o bloco
  "Locais perto de você" e o do botão de localização da home; o JS é `public/javas/localizacao.js`)
- Login → `public/css/login.css` (o login que o grupo fez)
- Cadastro → `public/css/cadastro.css` + `public/javas/cadastro.js` (o cadastro que a Mari fez, 29/09;
  a tela de escolha com o `style1.css` do grupo, bloco `.pagina--escolha`)
- Painéis → `public/css/painel.css` + `crud.css`; menu (o símbolo abre e fecha) em `public/javas/menu.js` (`agendamento.css` era das telas de consulta e não
  é mais usado por nenhuma tela)
- Menus de cada tipo de conta → `config/navegacao.php`. **Item de menu sem tela é link morto:**
  exames, receitas, atestados, prontuário, "resumo da saúde", financeiro e relatórios foram
  tirados dos mockups de propósito (fora do escopo).

**Tom de voz:** direto, transparente, acolhedor sem ser íntimo, calmo. Segunda pessoa, frase
curta: "Vida Plena - Centro foi desativada e não aparece mais na busca." — não "Informamos que a
sua solicitação foi processada com sucesso".

**Evitar:** qualquer palavra que sugira leitura clínica ("estável", "risco", "alerta de saúde");
"validado" (o certo é **"conferido na base simulada do PointMed"**); urgência fabricada
("últimas vagas"); jargão de sistema; emoji em e-mail.

---

## 9. Decisões e o porquê

| Decisão | Por quê |
|---|---|
| Refazer em **Laravel**, em vez de evoluir o PHP puro | o protótipo tinha furos de segurança (sessão, senha, preço vindo do formulário) e nenhuma estrutura para crescer |
| **Laravel 12**, não 13 | o 13 exige PHP 8.3, e o XAMPP parou no 8.2.12 |
| **Blade + CSS próprio + Alpine**, sem Node/npm (24/09) | as telas nunca usaram Tailwind de fato; tirar o build tirou um programa da máquina de cada um |
| Repositório = **projeto completo** + `index.php` na raiz (24/09) | antes cada um montava o Laravel e copiava pastas; agora é `composer run setup` |
| **Bases simuladas** para CRM, CNPJ e carteirinha (24/09) | a API do CFM é paga (R$ 772/ano, exige CNPJ); carteirinha não tem padrão nacional nem API; a conferência humana travava a demonstração |
| Convênios e planos **100% fictícios** (24/09) | não usar nome de operadora real sem contrato |
| Convênio aceito pelo **médico**, não pelo endereço | simplifica o cadastro; o aviso "confirme na recepção" cobre a exceção |
| ~~Preço por vínculo + especialidade~~ → **faixa de preço por unidade**, escolhida pela clínica (05/10) | sem agendamento não há valor a cobrar no sistema; a faixa ($ a $$$$) informa sem prometer preço exato |
| Comentário de avaliação **público**, autor só como "Ana L." (05/10; antes era privado) | decisão do grupo: o comentário ajuda quem escolhe; o nome curto e sem foto protege quem escreveu |
| Acessibilidade **saiu** (01/10) | só servia ao médico da consulta; sem consulta ficou sem finalidade, e a LGPD manda não guardar dado sensível sem finalidade |
| Sem pagamento, SUS e teleconsulta | fora do que um TCC consegue fazer direito até 20/10 |
| **Sem agendamento** (01/10) e médico **sem conta** | decisão do grupo (documento "Modificações - 01/10"): o PointMed virou um guia de clínicas perto do usuário |
| Distância com coordenada **aproximada** do bairro/cidade + localização do navegador (29/09); CEP com raio por ViaCEP + Nominatim, com a aproximada como reserva (07/10) | sem internet na banca a busca continua funcionando com a aproximada; em linha reta e "aproximada" para não prometer o caminho de carro |
| Locais por distância numa **tela nova** (`/locais`) (29/09) | a busca de médicos já funcionava e tinha teste; as duas se ligam por link |
| Exclusão de conta **anonimiza, não apaga** (30/09) | as notas entram na média dos locais e médicos; a LGPD (art. 16) aceita guardar o dado anonimizado. Só o usuário se exclui: a clínica tem médicos e unidades ligados a ela, e para ela continua o "bloquear" do admin |
| Médico **não se cadastra sozinho**: entra pela clínica (29/09) | plano do app de 28/09; a clínica já cadastrava o médico conferindo o CRM na base simulada, então nada de regra mudou — só sumiu um caminho a mais |
| Galeria da unidade **no banco**, pela `FotoDePerfil` (13/10) | o Render apaga arquivo enviado; a mesma classe já tira os metadados (GPS) e limita tamanho e tipo |
| Rodapé sem "dados fictícios", com "sem contrato com operadora" (13/10) | nota de 05/10 do Lucas; o aviso que o AGENTS §3 exige continua em toda página pública |
| Unidade **desativada guarda os médicos** e a clínica pode desativar a última (12/10) | desativar é reversível: reativar volta tudo como estava, sem a clínica refazer os vínculos; a mensagem avisa quando a clínica sai da busca |

**Becos sem saída (não repita):** validar CRM de graça por código; validar carteirinha por
algoritmo; consultar o COMPROVA da ANS; checar horário livre só com SELECT antes do INSERT
(duas pessoas passam juntas — use o índice único); XAMPP com PHP 8.3 (não existe).

---

## 10. Equipe, cronograma e regras de trabalho

| Pessoa | Frente |
|---|---|
| **Sidney** | back-end: regras de negócio, Policies, FormRequests, busca por distância, segurança e o site no ar (Render + Aiven) |
| **Cipriano** | front-end: layout, componentes e telas (seção 7) |
| **Mariana** | documentação + área administrativa |
| **Nicolle** | documentação + qualidade: textos da interface, roteiro de teste, dados de demonstração |
| **Murilo** | fundação + revisão de Pull Request antes do merge |

| Período | Foco |
|---|---|
| 18–24/09 | fundação: projeto rodando, banco populado ✅ |
| 25–30/09 | cadastros, perfis, telas internas, busca por distância, site no ar ✅ |
| 01–08/10 | reorganização sem agendamento (01/10), PointMed (05/10), integração na `main` (08/10) ✅ |
| 09–15/10 | fechamento: unidades, segurança (12/10) ✅, revisão geral (12/10) ✅, decisões pendentes (§6) |
| 16–20/10 | integração, testes, documentação, ensaio — **não construir nada novo** |

*(A tabela de pessoas é a de 18/09; o cronograma foi atualizado em 12/10 para o que de fato aconteceu.)*

**Regras para todos:**
1. **Branch por frente. Ninguém commita direto na `main`.**
2. **Migration já aplicada não se edita** — cria-se uma nova.
3. Commits pequenos, uma intenção por commit.
4. **Atualize a seção 6 deste README** a cada etapa concluída.
5. Regra de negócio mora em Policy, FormRequest, Model ou constraint — nunca em `if` na Blade.
6. Não reescreva o código dos outros depois que está na `main`: comente no PR.
7. Faça o **caminho feliz inteiro** antes de caprichar em qualquer tela.

**Riscos:** o back-end concentrado numa pessoa (mitigação: código comentado explicando o porquê,
testes automáticos); a curva de Laravel do grupo (leiam os comentários); o design promete mais
que o escopo (use `config/navegacao.php`); a banca perguntar da "verificação de CRM" (resposta
honesta: base simulada, e por que a API oficial ficou de fora).

---

## 11. Histórico de correções

> Histórico: boa parte dos itens é da versão com agendamento (até 01/10/2026).

Em 24/09/2026 o código **rodou pela primeira vez** (antes só tinha sido escrito). Foram achados
e corrigidos 18 problemas — todos com teste automático hoje:

| # | O que acontecia |
|---|---|
| 1 | Todas as telas do médico e da clínica davam 404 (a rota do perfil público "engolia" `/medico/agenda`) |
| 2 | Nenhum paciente conseguia se cadastrar sem marcar o consentimento de acessibilidade |
| 3 | Cadastro de médico com CRM válido dava erro 500 com "anos de atuação" em branco |
| 4 | Cadastro de clínica recusava CEP digitado com hífen |
| 5 | Por convênio dava para marcar especialidade que o médico não atende |
| 6 | Médico podia marcar como "realizada" uma consulta que ainda não aconteceu |
| 7 | Motivo de cancelamento longo derrubava a página |
| 8 | Mensagens de erro em inglês |
| 9 | Fuso horário em UTC ("agora" 3h adiantado) |
| 10 | Todo cancelamento era marcado como tardio (sinal do `diffInHours` no Carbon 3) |
| 11 | Conta bloqueada continuava navegando |
| 12 | Protótipo antigo e Laravel usavam o mesmo banco e as tabelas colidiam |
| 13 | `/register` do Breeze criava conta sem tipo |
| 14 | Acessibilidade podia ser salva sem descrição |
| 15 | Mensagens sem acento |
| 16 | Médico não conseguia cancelar consulta futura |
| 17 | Troca de senha aceitava mais de 72 caracteres |
| 18 | Os testes quebravam com o `APP_URL` do XAMPP |
| 19 | *(28/09)* Médico com a **conta bloqueada** continuava com horários livres e recebia consulta |
| 20 | *(28/09)* Por convênio dava para marcar especialidade cujo preço estava **desativado** naquele lugar |
| 21 | *(28/09)* O **motivo do bloqueio**, quem bloqueou e quando **nunca eram gravados** (campos fora do `$fillable`, descartados em silêncio) |
| 22 | *(28/09)* "Aprovar" CRM no admin não conferia a base simulada — um CRM cassado ganhava a etiqueta "conferido" |
| 23 | *(28/09)* Página pública da clínica listava médico bloqueado e especialidade sem preço (o paciente caía em "sem vaga") |
| 24 | *(28/09)* Data inválida na URL (agendar pela clínica, filtro de consultas do admin) dava erro 500 |
| 25 | *(28/09)* Especialidade com nome que gera o mesmo endereço de outra dava erro 500 |
| 26 | *(28/09)* Mensagens de erro sem acento nos cadastros e no agendamento; filtro das consultas do paciente sumia ao trocar de página |
| 27 | *(28/09, 2ª rodada)* Agendar **pela clínica** mostrava horários de especialidade **desativada** pelo admin; o paciente só era barrado na confirmação |
| 28 | *(28/09, 2ª rodada)* Duplo clique em "Enviar avaliação" dava **erro 500** (o UNIQUE recusava o segundo envio, com a avaliação já salva) |
| 29 | *(28/09, 2ª rodada)* A tela da consulta por convênio, que abre logo depois de agendar, não repetia o aviso "Confirme na recepção..." |
| 30 | *(28/09, 3ª rodada)* Preço digitado com ponto (`150.00`) era salvo como **R$ 15.000,00** (preço do médico e grade da clínica) |
| 31 | *(28/09, 3ª rodada)* A **acessibilidade** do paciente aparecia na agenda da clínica e, na do médico, até em consulta cancelada ou realizada |
| 32 | *(28/09, 3ª rodada)* Dava para se cadastrar com o **CRM de outra pessoa** (a base simulada não conferia o nome) |
| 33 | *(28/09, 3ª rodada)* Médico **rejeitado** pelo admin voltava a "verificado" trocando o CRM no perfil e reaparecia na busca |
| 34 | *(28/09, 3ª rodada)* Perfil público mostrava "Agendar aqui" em **clínica bloqueada** (caía em 404) e preço de especialidade desativada |
| 35 | *(28/09, 3ª rodada)* A busca mostrava médico **sem lugar para agendar** (ou sem preço da especialidade buscada); filtro em lista na URL dava erro 500 |
| 36 | *(28/09, 3ª rodada)* Ausência registrada de novo para cancelar as consultas (como a mensagem manda) ficava **duplicada** |
| 37 | *(28/09, 3ª rodada)* "Aprovar" carteirinha no admin não conferia a base simulada |
| 38 | *(28/09, 3ª rodada)* **Caixas de marcar quebradas** no cadastro de médico (só aparecia "C", "D"...), no de paciente e no de médico pela clínica (marcada parecia desmarcada) |
| 39 | *(28/09, 3ª rodada)* No celular, as abas de "Minhas consultas" passavam da largura da tela; comentários antigos no código contradiziam o AGENTS (senha "6 a 10", convênio "não pode ser inventado") |
| 40 | *(29/09)* Pelo XAMPP (`/FacilMed`), `/register` e `/cadastro/medico` redirecionavam para `http://localhost/cadastro` (404): o `Route::redirect` perde a subpasta. Agora usam `redirect()->route()` |
| 41 | *(29/09)* O mesmo paciente podia marcar duas consultas no mesmo horário com médicos diferentes, e os dados de demonstração já vinham assim (a Ana aparecia em dois lugares no mesmo dia e hora) |

---

## 12. Protótipo antigo

`prototipo-antigo/` guarda a primeira versão, em PHP puro, sem framework: `php/`, `paginas/`,
`css/`, `js/`, `imgs/`, `banco/`, e `referencia-original/` (os arquivos do protótipo que
inspiraram o modelo novo). **O sistema em Laravel não usa nada dali** — o visual da home e do
login já foi trazido para `resources/views/` e `public/css/`. Não evoluir; serve para consulta
e para o capítulo do TCC sobre a evolução do projeto.

Para abrir: importe `prototipo-antigo/banco/bancofacilmed.sql` no phpMyAdmin (cria o banco
**separado** `facilmed_prototipo`) e acesse
`http://localhost/FacilMed/prototipo-antigo/paginas/index.html`.

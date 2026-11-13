# Entrelinhas API

API REST Laravel da biblioteca pessoal Entrelinhas, integrada ao aplicativo Flutter.

**Aluno:** Vinicius Donini Santos Machado

**Disciplinas:** Programação Orientada a Objetos para Web, Paradigmas de Linguagem de Programação e Programação para Dispositivos Móveis.

**Flutter:** [Viniciusdonini/entrelinhas-flutter](https://github.com/Viniciusdonini/entrelinhas-flutter)

**API:** [Viniciusdonini/entrelinhas-api](https://github.com/Viniciusdonini/entrelinhas-api)

## Recursos e responsabilidades

A API oferece **quatro recursos com CRUD completo**, além da autenticação:

| Recurso | Caminho | Campos principais |
| --- | --- | --- |
| Autores | `/api/authors` | `name`, `bio` |
| Gêneros | `/api/genres` | `name`, `description` |
| Estantes | `/api/shelves` | `name`, `description`, `location` |
| Livros | `/api/books` | Título, autor, gênero, páginas, estado, notas, capa e estante opcional |

Cada família oferece `GET` da coleção, `POST`, `GET /{id}`, `PUT /{id}` e `DELETE /{id}`. Login não é contado como um dos quatro recursos.

```text
Route → Form Request/Controller → Service → Repository → Eloquent/banco
```

- **Migrations:** tabelas, índices e relações.
- **Models:** dados, casts e relacionamentos.
- **Factories:** geração de dados de desenvolvimento e teste.
- **Seeders:** preparação da biblioteca de demonstração.
- **Controllers:** entrada e saída HTTP.
- **Requests:** validação e autorização da entrada.
- **Services:** regras de negócio e coordenação.
- **Repositories:** consultas e persistência limitadas ao usuário.

A interface Flutter segue Screen → Service → Repository → ApiClient. Não é necessário um frontend web Laravel para avaliar esta API.

## Ambiente

Use **PHP 8.4.1 ou superior**, Composer e SQLite; habilite `pdo_sqlite`, `sqlite3` e `fileinfo`. O ambiente de preparação utiliza PHP 8.5, Laravel 13.42 e Sanctum 4.4. O mínimo 8.4.1 considera as dependências Symfony registradas no `composer.lock`; mantenha esse arquivo para reproduzir a instalação.

Não é necessário instalar Node.js ou compilar frontend para executar os endpoints deste projeto.

## Primeira instalação

Na pasta da API:

```shell
composer install
php -r "copy('.env.example', '.env');"
php artisan key:generate
```

Copie o `.env.example` somente na primeira instalação; não substitua um `.env` já configurado.

No `.env` local, confira `DB_CONNECTION=sqlite` e preencha `DEMO_NAME`, `DEMO_EMAIL` e `DEMO_PASSWORD` com os dados que você usará na demonstração. Não coloque esses valores no README ou no Git. A conta de demonstração é preparada pelo seeder da biblioteca chamado pelo `DatabaseSeeder`. Se `DEMO_PASSWORD` estiver ausente, o seeder avisa e não cria essa conta.

Se o arquivo SQLite ainda não existir, crie-o:

```shell
php -r "is_file('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
```

Inicie o servidor:

```shell
php artisan serve --host=127.0.0.1 --port=8000
```

Base da API: `http://localhost:8000/api`. Verificação pública de disponibilidade: `GET /api/health`.

O servidor acima atende o computador local e o Android Emulator por `10.0.2.2`. Um celular físico exige acesso à rede local, host de escuta apropriado e a URL do computador na configuração do Flutter. Uma implantação remota deve usar HTTPS.

## Autenticação

| Método | Rota | Resultado |
| --- | --- | --- |
| `POST` | `/api/auth/register` | Cadastro com nome, e-mail, senha e confirmação |
| `POST` | `/api/auth/login` | Login por e-mail e senha |
| `GET` | `/api/auth/me` | Usuário autenticado |
| `POST` | `/api/auth/logout` | Encerramento da sessão/token atual |

Login e cadastro retornam `data.user` e `data.token`. Os recursos protegidos usam:

```http
Authorization: Bearer TOKEN_OBTIDO_NO_LOGIN
Accept: application/json
```

O exemplo é um marcador, não um token real. O cliente recebe um token Sanctum e não deve persistir credenciais no código. O logout invalida a sessão correspondente.

## Demonstrar pelo rest.http

O arquivo [rest.http](rest.http) é a interface executável de demonstração exigida em POOWeb. Abra-o no VS Code com a extensão [REST Client](https://marketplace.visualstudio.com/items?itemName=humao.rest-client).

1. Inicie a API e confirme o `health`.
2. Confira o bloqueio da chamada sem token.
3. Execute a requisição nomeada `login`.
4. Execute os blocos de autores, gêneros, estantes e livros na ordem do arquivo.
5. Exclua o livro antes de excluir os catálogos criados.
6. Execute logout e confira que o token deixou de autorizar chamadas.

As credenciais são lidas do `.env` ao lado de `rest.http` por `{{$dotenv DEMO_EMAIL}}` e `{{$dotenv DEMO_PASSWORD}}`. O token e os IDs vêm de respostas nomeadas; não é necessário colar tokens no arquivo.

O bloco opcional de cadastro utiliza `REGISTER_EMAIL`, que deve ser preenchido no `.env` com um endereço ainda não utilizado. Ele cria uma conta separada e não é necessário para o fluxo principal da conta preparada pelo seeder.

A sintaxe de variáveis e respostas está documentada pelo próprio [REST Client](https://github.com/Huachao/vscode-restclient#request-variables).

## Contrato dos livros

Criação e edição recebem:

```json
{
  "title": "Título informado pelo usuário",
  "author": "Nome do autor",
  "genre": "Nome do gênero",
  "totalPages": 240,
  "currentPage": 0,
  "status": "wantToRead",
  "notes": "",
  "coverUrl": "",
  "shelfId": null
}
```

Os nomes de autor e gênero são resolvidos dentro da biblioteca do usuário. A resposta contém os dados do livro com IDs como strings e datas ISO 8601. A estante é opcional e deve pertencer ao mesmo usuário.

| Regra | Limite/comportamento |
| --- | --- |
| Título | Obrigatório, até 120 caracteres |
| Autor | Obrigatório, até 100 caracteres |
| Gênero | Opcional, até 60 caracteres |
| Total de páginas | Inteiro entre 1 e 20.000 |
| Página atual | Entre zero e o total |
| `wantToRead` | Página atual igual a zero |
| `finished` | Página atual igual ao total |
| `reading` na última página | Normalizado para `finished` pelo service |
| Notas | Até 2.000 caracteres |
| Capa | Vazia ou link HTTPS válido até 2.048 caracteres, sem credenciais |
| Datas | Criação preservada; alteração controlada pelo servidor |

## Respostas e segurança

| Código | Uso |
| --- | --- |
| `200` | Consulta ou edição concluída |
| `201` | Registro criado |
| `204` | Exclusão ou logout concluído, sem corpo |
| `401` | Sessão ausente ou inválida |
| `404` | Registro inexistente ou fora da biblioteca acessível |
| `409` | Catálogo ainda referenciado por um livro |
| `422` | Entrada inválida |

Todas as operações sobre livros e catálogos exigem autenticação. As consultas são restritas ao dono; referências a recursos de outro usuário não são aceitas. O servidor define o proprietário, não um `user_id` enviado pelo cliente.

A exclusão de um catálogo em uso precisa respeitar a regra de dependência. O `rest.http` remove primeiro seu livro de teste, evitando excluir catálogos referenciados.

Arquivos `.env`, tokens, senhas, banco SQLite local, logs e dependências instaladas não devem ser enviados ao GitHub.

## Testes e qualidade

```shell
php artisan test
php vendor/bin/pint --test
composer check-platform-reqs
```

**Resultados de 13/11/2026:** a suíte da API aprovou **41 testes e 435 asserções**, com PHPUnit 12.5.44 e SQLite em memória. Ela cobre autenticação, os quatro CRUDs, validação, isolamento entre usuários e regras de negócio. O Pint aprovou os arquivos PHP da API, e `composer check-platform-reqs` confirmou os requisitos da instalação.

No ambiente Windows desta execução, um runner local privado carregou o mesmo autoload e a mesma suíte para contornar um retorno incorreto de metadados de `is_readable`. Os arquivos de `vendor/`, os testes e o manifesto de configuração original não foram modificados por essa adaptação. Em uma instalação PHP normal, use `php artisan test` como indicado acima.

A integração Flutter/Chrome também aprovou dois testes contra a API real em `http://127.0.0.1:8000`: CRUD pelo service/repository e captura das sete telas. Os resultados anteriores de Firestore não foram usados para aprovar esta versão.

## Apresentação e entrega

A apresentação é presencial, tem **cinco minutos**, não exige slides nem vídeo, e segue **tema verbal → sistema → código**. O Flutter demonstra o uso do sistema; `rest.http` oferece a demonstração dos endpoints; os arquivos de camadas permitem explicar o código.

Para Paradigmas, envie **somente os links dos dois repositórios finais em texto online**. Não anexe arquivos ou código nessa entrega. A versão publicada deve corresponder à apresentada e estar acessível aos professores.

A próxima terça após 13/11/2026 é **17/11/2026**. O prazo anterior era 15/11/2026 às 23h59; não presuma que a data de apresentação prorroga o prazo do Moodle.

O desenvolvimento contou com apoio de IA, permitido no enunciado. O aluno deve revisar o código, entender suas decisões e ser capaz de explicá-lo.

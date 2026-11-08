# Módulo de Cursos, Turmas e Matrículas

Implementação sobre o projeto base da disciplina paradigmas-prog-20262 (Laravel 12).

## Instalação

Requisitos: PHP 8.2 ou superior, Composer e extensões PDO SQLite (ou PDO MySQL), mbstring e XML.

```bash
composer install
```

Copie `.env.example` para `.env` (no Windows: `copy .env.example .env`; no Linux: `cp .env.example .env`). Para começar com SQLite, crie o arquivo vazio `database/database.sqlite` e configure no `.env`:

```dotenv
DB_CONNECTION=sqlite
```

Remova ou comente `DB_DATABASE` para usar o caminho SQLite padrão do Laravel. Para MySQL, configure `DB_CONNECTION=mysql`, host, porta, banco, usuário e senha, com um banco já criado.

```bash
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

O seeder original cria `admin@admin.com` / `admin123` para desenvolvimento. Altere essa senha se publicar a aplicação. Não envie `.env` ao GitHub.

## Autenticação e rotas

A base original registra as rotas **sem prefixo `/api`**. URL local: `http://127.0.0.1:8000`.
Envie `Accept: application/json` e `Content-Type: application/json`.

1. `POST /login` com `{"email":"admin@admin.com","password":"admin123"}`.
2. Copie o campo `token` da resposta.
3. Nas outras requisições envie `Authorization: Bearer SEU_TOKEN`.

| Método | Rota | Finalidade |
| --- | --- | --- |
| GET | /courses?name=TADS | Listar cursos, com filtro opcional e turmas |
| POST | /courses | Criar curso |
| GET | /courses/{id} | Consultar curso com turmas |
| PUT / PATCH | /courses/{id} | Atualizar curso |
| DELETE | /courses/{id} | Excluir curso sem turmas |
| GET | /classrooms?course_id=1 | Listar turmas com seus cursos |
| POST | /classrooms | Criar turma com curso |
| GET | /classrooms/{id} | Consultar turma, curso e usuários |
| PUT / PATCH | /classrooms/{id} | Atualizar turma ou transferir de curso |
| DELETE | /classrooms/{id} | Excluir turma e seus vínculos |
| POST | /classrooms/{id}/users | Adicionar ou remover matrícula |

Criar curso:

```json
{"name":"TADS","description":"Análise e Desenvolvimento de Sistemas"}
```

Criar turma:

```json
{"name":"Turma A","vacancies":30,"course_id":1}
```

Matricular usuário existente:

```json
{"user_id":2,"action":"add"}
```

Remover matrícula: envie o mesmo POST com `"action":"remove"`. Repetir inclusão não duplica a matrícula; repetir remoção mantém o vínculo ausente. O endpoint retorna `200` com a turma, curso e usuários atuais. Um usuário pode pertencer a várias turmas.

Criação retorna `201`; exclusão `204`; registro inexistente `404`; validação `422`; falta de autenticação `401`. PUT exige os campos obrigatórios; PATCH aceita atualização parcial. Cursos exigem nome entre 2 e 255 caracteres; descrição é opcional. Turmas exigem nome, número inteiro de vagas entre 1 e 500 e um curso existente. O campo vagas é cadastral; esta atividade não implementa limite de matrículas por capacidade.

## Arquitetura e banco

- `CourseController` → `CourseService extends BaseService` → `CourseRepository extends BaseRepository`.
- `CourseRequest` e `ClassroomUserRequest` concentram validações; Resources controlam o JSON.
- `Course::classrooms()` é hasMany e `Classroom::course()` é belongsTo.
- `Classroom::users()` e `User::classrooms()` são belongsToMany pela tabela `classroom_user`, com timestamps e chave única composta.
- A matrícula passa por `ClassroomController` → `ClassroomService` → `ClassroomRepository`.
- Chaves estrangeiras removem vínculos ao excluir usuários ou turmas. Cursos com turmas não podem ser excluídos (422).
- As novas migrations são incrementais. Se já houver turmas, elas são associadas a um curso de migração e podem ser transferidas depois. Não é necessário apagar o banco nem usar `migrate:fresh`.

## Verificação

```bash
php artisan test
php artisan route:list --path=courses
php artisan route:list --path=classrooms
php artisan l5-swagger:generate
```

Os testes usam SQLite em memória e cobrem CRUD, filtros, autenticação, validação, relações, transferência de curso, matrículas repetidas, remoções e cascatas. Não utilizam o banco de desenvolvimento.

Importe `docs/Cursos_Turmas.postman_collection.json` no Postman. Execute Login para preencher o token automaticamente, crie um curso e uma turma (IDs são salvos automaticamente), depois teste inclusão e remoção usando `user_id` de um usuário existente.

## Subir no seu fork

Extraia o ZIP e copie os arquivos da pasta do projeto para o seu fork local, preservando a pasta `.git` do seu fork. O ZIP omite dependências, dados locais, `.env` e o histórico Git da máquina de origem.

```bash
git add .
git commit -m "Implementa cursos, turmas e matriculas N-M"
git push origin main
```

Se sua branch se chama `master`, use `git push origin master`. Entregue no Moodle o link do seu fork, conforme solicitado na atividade.

# HANDOFF — ReviewFood (MVP)

> **Última atualização:** 27/11/2026  
> **Documento de referência (baseline):** `docs/EFT-RR-MVP-001_v1.1.md`  
> **Stack:** Laravel 13, PHP 8.3+, PostgreSQL, Blade, Tailwind CSS, Alpine.js, PHPUnit

---

## 1. Acordo de Trabalho (LEIA ANTES DE CONTINUAR)

Este projeto está sendo desenvolvido com foco em **aprendizado prático de Laravel pelo desenvolvedor**:

* **NÃO entregar código pronto da aplicação** (`app/`, `database/migrations/`, `database/seeders/`, `routes/`, `resources/views/`). Em vez disso: orientar passo a passo, explicar o *porquê* dos conceitos, revisar o código escrito pelo usuário e apontar bugs/melhorias de forma didática.
* **Exceção autorizada pelo usuário:** o assistente **pode escrever diretamente os testes automatizados** (`tests/Feature/*`, `tests/Unit/*`) quando solicitado — **somente os testes**, sem alterar código de produção.
* **Execução de comandos:** o usuário prefere rodar `php artisan test`, `migrate`, `db:seed` e comandos de banco no próprio terminal.

---

## 2. O que já foi concluído

### 2.1 Fundação e Ambiente (Fase 0 — quase completa)
* **PostgreSQL local:** bancos dedicados `reviewfood` e `reviewfood_test` criados com o usuário do `.env` como `OWNER` (resolvendo permissões do schema `public` / `pg_database_owner` do PostgreSQL 15+).
* **Timezone (`RN-035`, `RNF-PER-008`):** `config/app.php` ajustado para `'timezone' => env('APP_TIMEZONE', 'America/Sao_Paulo')`.
* **Testes em PostgreSQL real:** `phpunit.xml` configurado para `DB_CONNECTION=pgsql` e `DB_DATABASE=reviewfood_test`.
* **Ainda pendente da Fase 0:** instalar e validar compatibilidade com Laravel 13 dos pacotes externos (`laravel/socialite`, `league/flysystem-aws-s3-v3`, `alpinejs` e, se escolhido na Fase 1B, `laravel/fortify`).

### 2.2 Migrations (`database/migrations/`) — 100% validadas
* Todas as **16 migrations** (14 tabelas de domínio + tabelas de infraestrutura Laravel `sessions`, `password_reset_tokens`, `cache`, `jobs`) foram revisadas contra a v1.1 e validadas no PostgreSQL com o ciclo completo `migrate` → `migrate:rollback` → `migrate:fresh`.
* Inclui `sessions.user_id` como `foreignUuid`, constraints `CHECK`, índices parciais e case-insensitive, FKs compostas em `visit_photos` e `reviews`, e a função/trigger `enforce_group_active_member_limit()` com `SELECT ... FOR UPDATE` e `IS DISTINCT FROM`.
* *Notas conhecidas e aceitas:* `$table->timestampsTz()` (nullable, sem `DEFAULT NOW()`), PKs sem `DEFAULT gen_random_uuid()` (UUIDs gerados via `HasUuids` conforme Seção 9.1), e `cascadeOnUpdate()` extra em `categories.group_id` (inofensivo).

### 2.3 Enums (`app/Enums/`) — 6/6 concluídos
* `GroupStatus` (`ACTIVE`, `INACTIVE`)
* `GroupMemberStatus` (`ACTIVE`, `LEFT`)
* `GroupInvitationStatus` (`PENDING`, `ACCEPTED`, `DECLINED`, `EXPIRED`)
* `CategoryType` (`DEFAULT`, `CUSTOM`)
* `RestaurantStatus` (`TO_VISIT`, `VISITED`)
* `ReviewStatus` (`DRAFT`, `COMPLETED`)

### 2.4 Models (`app/Models/`) — 13/13 concluídos e revisados
* `User` (`implements MustVerifyEmail`, `#[Hidden(['password', 'remember_token'])]`, `#[Fillable]` sem `email_verified_at`; relacionamentos `groups`, `memberships`, `restaurantPreferences`, `visitParticipations`, `reviews`).
* `Group` (`members`, `memberships`, `invitations`, `categories`, `restaurants`, `tags`).
* `GroupMember` (`group`, `user`).
* `GroupInvitation` (`#[Hidden(['token_hash'])]`, `group`, `invitedBy`).
* `Category` (`group`, `createdBy`, `restaurants`).
* `Restaurant` (`group`, `category`, `createdBy`, `visits`, `preferences`).
* `RestaurantPreference` (`restaurant`, `user`).
* `Visit` (`restaurant`, `createdBy`, `tags`, `participants`, `items`, `photos`, `reviews`).
* `VisitParticipant` (`visit`, `user`).
* `VisitItem` (`visit`, `createdBy`; casts `quantity => integer`, `unit_price => decimal:2`).
* `VisitPhoto` (`visit`, `uploadedBy`; cast `size_bytes => integer`).
* `Tag` (`group`, `createdBy`, `visits`).
* `Review` (`visit`, `user`; casts `integer` nas 4 notas, `ReviewStatus::class`, `completed_at => datetime`).

### 2.5 Seeders (`database/seeders/`) — concluído e testado
* `CategorySeeder`: idempotente (`firstOrCreate` por `name`, `type = CategoryType::DEFAULT`, `group_id = null`) com as 15 categorias do Apêndice B; validado executando 2 vezes seguidas sem duplicar nem falhar.
* `DatabaseSeeder`: chama `$this->call(CategorySeeder::class)`.

### 2.6 Fase 1A — `GroupService` + `GroupServiceTest`
* **`app/Services/GroupService.php`** — todos os **5 métodos** implementados pelo usuário e revisados/aprovados:
  1. `createPersonalGroup(User $user): Group`
  2. `invite(Group $group, User $inviter, string $email): array` — normalização `RN-029`, token aleatório 64 chars com `hash('sha256', ...)`, expiração automática de pendente vencido.
  3. `acceptInvitation(GroupInvitation $invitation, User $user): Group` — `DB::transaction` + `lockForUpdate()` no grupo e no convite, validação de grupo individual vazio, encerramento do vínculo/grupo antigo antes do novo (`CT-001`, `CT-002`).
  4. `declineInvitation(GroupInvitation $invitation, User $user): GroupInvitation` — `lockForUpdate()`, validações e marcação `DECLINED` + `responded_at`.
  5. `unlinkCouple(Group $group, User $actor): array` — `lockForUpdate()`, encerra grupo (`INACTIVE`) e vínculos (`LEFT` + `left_at`), expira apenas convites `PENDING` preservando histórico, cria 2 novos grupos individuais `ACTIVE` reutilizando `createPersonalGroup()` (`UC-08`, `CT-012`).
* **`tests/Feature/GroupServiceTest.php`** — **25 testes** completos contra PostgreSQL real:
  * 2 testes de `createPersonalGroup` — **passaram**.
  * 6 testes de `invite` (incl. `$this->travel(8)->days()`) — **passaram**.
  * 9 testes de `acceptInvitation` (incl. `CT-001`, `CT-002` no Service e teste direto do trigger PostgreSQL) — **escritos, pendentes de confirmação da execução pelo usuário**.
  * 4 testes de `declineInvitation` + 4 testes de `unlinkCouple` (`CT-012`) — **escritos, pendentes de confirmação da execução pelo usuário**.

---

## 3. Decisões Tomadas

1. **Valores de status de encerramento (`INACTIVE` / `LEFT` no lugar de `ENDED`):**
   * O usuário optou por manter `GroupStatus::INACTIVE` (`'INACTIVE'`) em `groups` e `GroupMemberStatus::LEFT` (`'LEFT'`) em `group_members`.
   * As migrations `2026_08_26_175138_create_groups_table.php` e `2026_09_01_133547_create_group_members_table.php` (incluindo `ck_group_members_dates`) foram atualizadas para refletir exatamente esses valores.
   * *Ação futura opcional:* atualizar a menção a `ENDED` em `docs/EFT-RR-MVP-001_v1.1.md` para manter 100% de sincronia com o código.
2. **Padrão dos Models (Laravel 13):**
   * Uso de PHP Attributes `#[Fillable([...])]` e `#[Hidden([...])]` acima da classe.
   * Uso de `HasUuids` em todos os 13 Models (sem `gen_random_uuid()` no banco).
   * `GroupMember` e `VisitParticipant` modelados como Models próprios (`extends Model`) acessados via `HasMany` (`memberships()` / `participants()`) para evitar falha de `attach()` em pivots com coluna `id` UUID `NOT NULL`.
   * Coluna inexistente `'role'` removida do `withPivot` de `User::groups()` e `Group::members()`.
3. **Nome padrão do grupo individual:** `createPersonalGroup()` atribui `'name' => $user->name` (mesmo limite `VARCHAR(120)` de `users.name`).
4. **Exceções de domínio nos Services:** `\DomainException` com mensagens descritivas em português (código interno em inglês conforme `ADR-009`).
5. **Decisão em aberto para a próxima sessão (Fase 1B):** escolher entre **Controllers Blade próprios** (máximo aprendizado de sessão/CSRF/auth por dentro) ou **Laravel Fortify** (backend de auth pronto + customização de `CreateNewUser`) para autenticação local.

---

## 4. Próximos Passos

### Imediato (início da próxima sessão)
1. **Rodar os 25 testes do `GroupServiceTest`** no terminal para confirmar que `acceptInvitation`, `declineInvitation`, `unlinkCouple` e o trigger PostgreSQL estão 100% verdes:
   ```powershell
   php artisan test --filter=GroupServiceTest
   ```
2. *(Opcional)* Rodar `.\vendor\bin\pint` para padronizar a formatação PSR-12/Laravel dos arquivos criados até aqui.

### Fechar Fase 1A — Autorização de Grupo
3. **Helper em `User`:** criar método auxiliar `activeGroup(): ?Group` (ou `activeMembership(): ?GroupMember`) para evitar repetir a query de grupo ativo em todos os Controllers e Policies.
4. **`GroupPolicy` (`app/Policies/GroupPolicy.php`):** implementar autorização para `view`, `invite`, `unlink` (e `accept`/`decline` para `GroupInvitationPolicy`, se separado) + testes de Policy.

### Fase 1B — Autenticação, Google OAuth e Telas de Vínculo
5. **Definir abordagem de auth local** (Controllers Blade próprios vs Laravel Fortify) e validar/instalar `laravel/socialite` (e `laravel/fortify` se escolhido) no Laravel 13.
6. **Autenticação local (`RF-AUT-001`, `002`, `005`, `006`, `007`):** cadastro (chamando `GroupService::createPersonalGroup()`), login com regeneração de sessão, logout, verificação de e-mail (`MustVerifyEmail`), recuperação de senha e edição de perfil.
7. **Google OAuth (`RF-AUT-003`, `RF-AUT-004`, `RNF-SEG-011`):** `Auth/GoogleAuthController` com proteção contra *pre-account takeover* (zerar `password` e invalidar tokens de reset se conta local prévia não estiver verificada) + teste `CT-017`.
8. **Fluxo web de Parceiro/Convites (`TEL-01`, `TEL-02`, `TEL-10`, `TEL-11`, `TEL-14`):** Controllers, Form Requests, envio de e-mail de convite (Mailable) com o token puro retornado pelo `GroupService::invite()`, telas mobile-first com Tailwind.

### Roadmap das Fases Seguintes (Seção 14 da spec)
* **Fase 2 — Restaurantes, Categorias e Preferências:** `CategoryController`, `RestaurantController`, `RestaurantPreferenceController`, `RestaurantArchiveController`, `RestaurantPolicy`, validação *cross-group* de categorias (`RN-033`), listas *Quero conhecer* / *Visitados* / *Favoritos* com busca e ordenação (`CT-003`, `CT-009`, `CT-010`, `CT-015`).
* **Fase 3 — Visitas, Itens, Tags e Fotos:** `VisitService`, `VisitController`, `VisitPolicy`, `StorageService` (`league/flysystem-aws-s3-v3`, URLs assinadas TTL 15 min, remoção pós-commit `DB::afterCommit`), `TEL-06` dinâmica com Alpine.js + `fetch()` + CSRF, compressão client-side `<canvas>`, exclusão de visita com retorno a `TO_VISIT` (`CT-004`, `CT-005`, `CT-011`, `CT-014`, `CT-016`).
* **Fase 4 — Avaliações Secretas:** `ReviewService`, `ReviewController`, `ReviewPolicy`, ocultação estrita server-side antes da revelação, revelação simultânea e recálculo pós-edição (`CT-006`, `CT-007`, `CT-008`).
* **Fase 5 — Indicadores, Filtros Avançados e Histórico Anterior (`TEL-15`):** agregações derivadas (`RN-022`, Seção 5.1), matriz completa de filtros (Seção 7.3) e `PastHistoryController` somente leitura via `VisitParticipant` (`CT-012`, `CT-013`).
* **Fase 6 — Hardening e Deploy:** revisão `CT-001` a `CT-017`, acessibilidade mobile (`360px`), workflow GitHub Actions de keep-alive do Supabase Free (a cada 3 dias), backup/restore e deploy no Render Free.

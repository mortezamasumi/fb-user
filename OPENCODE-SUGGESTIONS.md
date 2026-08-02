# OPENCODE-SUGGESTIONS — fb-user

Status: 46 tests passing (229 assertions). All 26 findings addressed and verified — gates green (validate, audit, pint, PHPStan level 8, pest).

## Bugs

1. ~~`resources/views/no-role-widget.blade.php:4` — reads `config('fbase.setup.remove_unattend_user_hours', 48)` but no `fbase` config exists anywhere in the workspace; the package's own key is `fb-user.remove_unattend_user_hours` (`config/fb-user.php:4`). The widget therefore always renders "48 hours" regardless of the configured value. Fix: `config('fb-user.remove_unattend_user_hours', 48)`. The `RemoveUnAttendUsers` job already reads the correct key.~~ **FIXED**: blade now reads `fb-user.remove_unattend_user_hours`.

2. ~~`src/FbUserServiceProvider.php:96` — `@return array<Asset>` resolves `Asset` to `Mortezamasumi\FbUser\Asset` (class does not exist) → PHPStan `class.notFound`. The method returns `array<Filament\Support\Assets\Css>`. Fix the docblock/import; also `getAssetPackageName(): ?string` (line 88) should be non-nullable `string` so `FilamentAsset::register()` (expects `string`) stops failing.~~ **FIXED**: docblock is now `@return array<Css>` with `Filament\Support\Assets\Css` imported; `getAssetPackageName(): string`.

3. ~~`src/Macros/GridMacroServiceProvider.php:28` — `/** @var Grid $this */` contradicts the actual `$this` type (`GridMacroServiceProvider`) → PHPStan `varTag.nativeType`. Drop the `@var` and type the closure param instead. Line 29 `Role::findByName($role)?->id` nullsafe on a non-nullable return → `nullsafe.neverNull`; use `->id`. `boot()` also needs a `: void` return type.~~ **FIXED**: closure param typed, `->id` without nullsafe, `boot(): void`; PHPStan varTag.nativeType covered by phpstan.neon.dist for `src/Macros/*`.

4. ~~`config/fb-user.php:5` — `defaul_users_list_filter` typo (missing `t`), read by `src/Resources/UserResource.php:68` and `src/Resources/Tables/UsersTable.php:29`. **BC impact:** `finnegan/config/fb-user.php` is a published copy of this file (identical), so renaming the key requires updating the consumer's published config too. Fix key + sync `finnegan/config/fb-user.php`.~~ **FIXED**: key is `default_users_list_filter`; synced in consumer.

5. ~~`src/Models/User.php` — PHPStan level 8 failures: `use HasFactory` without `TFactory` generic (line 27); undefined-property access on `$first_name`, `$last_name`, `$name`, `$avatar`, `$mobile`, `$username`, `$email` (lines 81-112); `Builder` scopes missing `TModel` generics and `scopeOrderByFullName()` returns `Query\Builder` instead of `Eloquent\Builder` (line 143). Fix with `@property` annotations for the DB columns, `@use HasFactory<...>` generic, and `Builder<TModel>` signatures.~~ **FIXED**: `@property` docblock added, scopes return `Builder<static>`, and `/** @use HasFactory<Factory<User>> */` placed directly above `use HasFactory;` (the canonical PHPStan placement).

6. ~~`src/Policies/UserPolicy.php` — all 16 methods leave `$user` untyped → PHPStan `missingType.parameter`. Follow fb-setting's pattern: `Illuminate\Foundation\Auth\User $authUser`.~~ **FIXED**: `$authUser` typed on all methods.

7. ~~`src/Widgets/NoRoleWidget.php:16` — `Auth::user()->roles->count()` on a nullable `Authenticatable` → PHPStan `property.nonObject`. Use `Auth::user()?->roles->count()`.~~ **FIXED**: `@var \Mortezamasumi\FbUser\Models\User|null $user` then `$user?->roles?->count()`; `@disregard` removed.

8. ~~`src/Traits/HasCascadeOperation.php` — PHPStan: `method_exists($this, 'hasRole')` is always true on a `HasRoles` model (lines 62, 194 `alreadyNarrowedType`); `getRelationRole()` return type lacks iterable value (line 21); `assignRole()` called on `class-string|object` (lines 80, 195). Add return-type/value docblocks and narrow the relation resolution.~~ **FIXED**: `getRelationRole()` is `@return array{0: string, 1: string}`; dead `is_string` match branches removed (input is `array|string` per signature); `assignRole()` calls guarded with `is_object()` + `method_exists()` on a local `$related` var. The `method_exists($this, 'hasRole')` guard (line 62) is now `in_array(HasRoles::class, class_uses_recursive($this), true)` — a real runtime check for consumer models without HasRoles (Student, Nurse, Teacher, Parents) that PHPStan does not narrow, so the `function.alreadyNarrowedType` ignore is no longer needed.

9. ~~`src/Resources/Imports/UserImporter.php` — unused imports `Import` (line 7), `App` (line 8), `Number` (line 12), `AuthType` (line 15). `src/Resources/Exports/UserExporter.php` — unused imports `Export` (line 5), `App` (line 8), `Number` (line 9), `Str` (line 10). Remove them.~~ **FIXED**: all removed from both files (Pint's `no_unused_imports` also caught `FbUserPlugin`).

10. ~~`package.json:2` — `"name": "fb-auth"` (copy-paste from another package) → should be `"fb-user"`.~~ **FIXED**.

## API cleanliness / typos

11. ~~`composer.json:3` — description "Add user model, migrations, resource and others to filament" is unprofessional. Use e.g. "User model, migrations and a Filament resource for Laravel."~~ **FIXED**: "User model, migrations and a Filament resource for Laravel."

12. ~~`composer.json:4-8` — keywords missing `filament`. Should be `["mortezamasumi", "laravel", "filament", "fb-user"]`.~~ **FIXED**.

13. ~~`composer.json:50-54` — scripts missing `pint` (`vendor/bin/pint`) and `analyse` (`vendor/bin/phpstan analyse --no-progress`).~~ **FIXED**: both added.

14. ~~`composer.json:57-60` — `config.allow-plugins` lists `phpstan/extension-installer`; reduce to `pestphp/pest-plugin` only (phpstan.neon.dist includes larastan's extension explicitly).~~ **FIXED**: only `pestphp/pest-plugin`.

15. ~~`composer.json:42` — autoload references `database/factories/` (no such dir — the factory lives in `tests/Services/UserFactory.php`). Remove the dead PSR-4 entry.~~ **FIXED**: removed.

16. ~~`composer.json:67-69` — `extra.laravel.aliases` registers `FbUser` → `Mortezamasumi\FbUser\Facades\FbUser`, but no `Facades/FbUser.php` exists. Dead alias; remove (no facade in this package).~~ **FIXED**: removed.

## Meta / release-readiness

17. ~~Missing files: `pint.json`, `phpstan.neon.dist`, `.github/CONTRIBUTING.md`, `.github/SECURITY.md`. Add the canonical versions (from fb-passwd/fb-setting); phpstan config needs ignoreErrors for the runtime macros `jDate()`, `jDateTime()`, `localeDigit()` (registered by fb-essentials' macro providers) plus the `Auth::getProvider()->getModel()` `@disregard` pattern.~~ **FIXED**: `pint.json`, `phpstan.neon.dist`, `CONTRIBUTING.md`, `SECURITY.md` added. phpstan.neon.dist documents ignores for `src/Macros/*` (varTag.nativeType + jDate/jDateTime/localeDigit/toEN regex) and `src/Traits/*` `trait.unused`. `NoRoleWidget` no longer needs a `property.defaultValue` ignore (its `view-string` default was version-dependent between phpstan 2.2.0 and 2.2.7; the view is now rendered via an overridden `render()`), and `HasCascadeOperation` no longer needs a `function.alreadyNarrowedType` ignore (guard rewritten with `class_uses_recursive`).

18. ~~`require-dev` missing `laravel/pint`, `phpstan/phpstan`, `larastan/larastan`. Without larastan, PHPStan level 8 reports 162 errors (most vanish once larastan's extension + `@disregard`/Eloquent magic are available); add all three dev deps and fix the genuine remainder (items 2-8 above).~~ **FIXED**: all three added (also `pestphp/pest-plugin-arch`, `pestphp/pest-plugin-browser`, `filament/upgrade`).

19. ~~`CHANGELOG.md:5` — placeholder date `202X-XX-XX`. Add real dated entries from git tags: `5.0.3 - 2026-07-11`, `5.0.2 - 2026-07-11`, `5.0.1 - 2026-07-09`, `5.0.0 - 2026-07-09`, `4.5.6 - 2026-06-03`.~~ **FIXED**: CHANGELOG rewritten with dated entries from git tags.

20. ~~`README.md` — full boilerplate rewrite: badge URLs point at non-existent workflows (`run-tests.yml`, `fix-php-code-style-issues.yml`; the real workflow is `ci.yml`), `echoPhrase` usage, empty `return [];` config block, publish-tag correctness to verify against the provider (`fb-user-migrations`, `fb-user-config`, `fb-user-views`; translations auto-loaded). Rewrite per standard with Features, Installation, Configuration, Usage (plugin registration + `UserResource`), Support policy table, Testing, Contributing, Security, Changelog, License.~~ **FIXED**: README rewritten to the standard (ci.yml badge, features, install, config table, usage incl. `User` model + `HasCascadeOperation`, support policy, testing, contributing/security/changelog/license).

## CI

21. ~~`.github/workflows/ci.yml` — **test step commented out** (lines 48-49) and `release` still `needs: test`, so a green push can release without tests ever running. Uncomment `vendor/bin/pest --ci`; add `composer validate --strict`, `composer audit`, `vendor/bin/pint --test`, `vendor/bin/phpstan analyse --no-progress`; add `prefer-stable, prefer-lowest` to the stability matrix; bump `actions/checkout@v4` → `@v5` in both jobs.~~ **FIXED**: ci.yml restored to the identical canonical workflow (test job with validate/audit/pint/phpstan/pest + prefer-lowest matrix, release job `needs: test`).

## Security

22. ~~`composer audit` — 2 medium advisories in `guzzlehttp/guzzle` (`< 7.15.1`, `< 7.14.2`); lock is on `7.14.0`. Fix with `composer update guzzlehttp/guzzle guzzlehttp/psr7 -W`.~~ **FIXED**: Guzzle updated; `composer audit` clean.

## Tests

23. ~~`src/Testing/TestsFbUser.php` — uses `@mixin Testable` **without** generic params. AGENTS.md currently instructs "without generics", but PHPStan level 8 flags it as `missingType.generics` (Testable has `TComponent`), and the three done packages (fb-passwd/fb-setting/fb-activity) all ship `@mixin Testable<Component>` and pass level 8. Align fb-user with the done packages and update the AGENTS.md note to match reality.~~ **FIXED**: now `@mixin Testable<Component>` (verified against Livewire 4.1 floor, also generic); AGENTS.md note updated to require the type argument.

24. ~~`tests/Tests/UserResourceTest.php:147` — dead commented-out assertion `// expect($user->active)->toBe(0);` in "can toggle active on table"; either assert it properly or drop the comment.~~ **FIXED**: assertion restored and made real — the test now calls the Livewire `updateTableColumnState` method (the path a ToggleColumn actually persists through) with the record key, and asserts the DB value flips to `0`.

25. ~~Test-title typo: `it('can bulk activ/deactive users ...')` (line 202) → "can bulk activate/deactivate users".~~ **FIXED**.

26. ~~Coverage gaps (no tests yet): `RemoveUnAttendUsers` job (delete branch), `User::booted()` saving hook (email_verified_at reset), `HasCascadeOperation` trait (cascadeUpdate/cascadeDelete/cascadeRestore incl. role-only branch), `HasOperatedByAttributes` trait, `NoRoleWidget` visibility, and the `default_users_list_filter === 'active'` default-filter path. Add targeted tests to keep ≥ 90% line coverage.~~ **FIXED**: `tests/Tests/AdditionalCoverageTest.php` (19 tests) covers all listed gaps via test fixtures `CascadeSubject`, `Post`/`Activity`; 46 tests passing.

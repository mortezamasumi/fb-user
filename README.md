# FB User — User Management for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mortezamasumi/fb-user.svg?style=flat-square)](https://packagist.org/packages/mortezamasumi/fb-user)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/mortezamasumi/fb-user/ci.yml?branch=main&label=tests&style=flat-square)](https://github.com/mortezamasumi/fb-user/actions?query=branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/mortezamasumi/fb-user.svg?style=flat-square)](https://packagist.org/packages/mortezamasumi/fb-user)
[![License](https://img.shields.io/packagist/l/mortezamasumi/fb-user.svg?style=flat-square)](LICENSE.md)

A Filament panel plugin that provides a user model, migrations and a complete `UserResource` (list, create, edit, import and export) for Laravel applications.

---

## Features

- **User resource** — full CRUD with a sortable, filterable table and role support
- **Cascade operations** — keep related records (e.g. students, nurses) in sync when a user is deleted, force-deleted or restored
- **Role-based visibility** — super-admins see all users; non-super-admins only see users without a role
- **Scheduled cleanup** — an hourly job removes users that have been unattended for a configurable number of hours
- **Import & export** — CSV import (with role assignment and gender/date handling) and export built on Filament actions
- **No-role widget** — an optional dashboard widget that renders when the authenticated user has no role
- **Localized** — ships English and Persian translations

---

## Installation

```bash
composer require mortezamasumi/fb-user
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="fb-user-migrations"
php artisan migrate
```

Add the plugin to your Filament panel provider:

```php
use Mortezamasumi\FbUser\FbUserPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FbUserPlugin::make(),
        ]);
}
```

---

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag="fb-user-config"
```

| Key | Default | Description |
| --- | --- | --- |
| `remove_unattend_user_hours` | `48` | Hours after which unattended users are removed by the hourly job |
| `default_users_list_filter` | `all` | Initial `active` filter state on the users list (`all` or `active`) |
| `navigation.*` | — | Filament navigation label, group, icon and sort options for the resource |

---

## Usage

### The `User` model

The package ships an abstract `Mortezamasumi\FbUser\Models\User` base model. Extend it in your application so the resource and auth guard share one class:

```php
use Mortezamasumi\FbUser\Models\User as BaseUser;

class User extends BaseUser
{
    // your application-specific user logic
}
```

### Cascade operations

The `HasCascadeOperation` trait lets a model keep its relations in sync with role changes:

```php
use Mortezamasumi\FbUser\Traits\HasCascadeOperation;

class Student extends Model
{
    use HasCascadeOperation;
}
```

```php
$user->cascadeUpdate(['student' => 'student']);      // delete/restore relations by role
$user->cascadeDelete(['student']);                   // delete relations (with or without role)
$user->cascadeRestore(['student' => 'student']);     // restore relations and re-assign role
```

### Scheduled cleanup

The `RemoveUnAttendUsers` job runs hourly and deletes users inactive for `remove_unattend_user_hours`. No extra setup is required once the plugin is registered.

---

## Support policy

| PHP | Laravel |
| --- | --- |
| 8.3 | 12 |

---

## Testing

```bash
composer test
```

The suite covers the resource pages (list, create, edit, delete, restore, force delete, bulk actions), filtering, import/export and the cascade operations against an in-memory SQLite database.

---

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security

If you discover a security vulnerability, please review our [security policy](.github/SECURITY.md) on how to report it.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes.

---

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md) for details.

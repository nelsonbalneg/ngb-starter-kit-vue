# Enterprise Starter Kit

Enterprise starter kit for Laravel, Vue, Inertia, TypeScript, Tailwind CSS, Fortify, and Spatie Laravel Permission with Teams.

## Stack

- Laravel 13 and PHP 8.3+
- Vue 3, Inertia.js, TypeScript, Vite
- Tailwind CSS and compact enterprise UI components
- Laravel Fortify authentication
- Spatie Laravel Permission with Teams for organization-scoped access
- Wayfinder route helpers

## Included Modules

- Dashboard
- Site Administration
- Authentication management: users, roles, permissions, role permissions
- Organization hierarchy and organization units
- Generic lookup maintenance
- Site settings, branding, and maintenance controls
- Optional SSO login and logout flow

## First Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
npm install
npm run build
```

For local development:

```bash
composer run dev
```

## Starter Seed Data

Seed data is configured in `config/starter-kit.php` and can be customized through `.env`.

Default users:

| Role        | Email                   | Password |
| ----------- | ----------------------- | -------- |
| super_admin | super_admin@example.com | password |
| super_admin | admin@example.com       | password |
| user        | user@example.com        | password |
| employee    | employee@example.com    | password |

Change `STARTER_DEFAULT_PASSWORD` before seeding for shared environments.

## Organization Scope

Organizations are used as Spatie Teams. Default seed values:

- Parent organization: `starter`
- Default workspace: `main`
- Sample workspace: `branch`

Permission checks must happen inside the active organization context:

```php
app(\Spatie\Permission\PermissionRegistrar::class)
    ->setPermissionsTeamId($organizationId);
```

## SSO

SSO is optional and disabled by default.

Important `.env` values:

```dotenv
SSO_ENABLED=false
SSO_BASE_URL=
SSO_USER_URL=
SSO_CLIENT_ID=
SSO_CLIENT_SECRET=
SSO_REDIRECT_URI="${APP_URL}/auth/callback"
SSO_LOGOUT_REDIRECT_URI="${APP_URL}/dashboard"
SSO_SCOPES="openid profile email"
SSO_STATE_VALIDATION=auto
SSO_DEFAULT_ROLE=employee
SSO_DEFAULT_ORGANIZATION_SLUG=main
```

When `SSO_ENABLED=true`, protected pages redirect unauthenticated users to SSO. Logout redirects to `SSO_LOGOUT_REDIRECT_URI`.

Never commit real SSO secrets.

## Quality Checks

```bash
composer run lint:check
npm run format:check
npm run types:check
php artisan test
```

Some legacy generated UI files may still need lint cleanup. New work should pass formatting, TypeScript, and PHP style checks before handoff.

## Development Rules

- Do not hardcode roles, organizations, menus, or permission visibility.
- Use permissions such as `users.view`, not role-name checks.
- Keep controllers thin; put business logic in services.
- Scope access by organization/team.
- Use named routes and Wayfinder helpers.
- Use confirmation dialogs for deletes.
- Use Inter, compact spacing, accessible controls, and enterprise-grade UI patterns.

## Using This As A Starter Kit

See [STARTER_KIT_INSTRUCTIONS.md](STARTER_KIT_INSTRUCTIONS.md) for copy-paste setup instructions.

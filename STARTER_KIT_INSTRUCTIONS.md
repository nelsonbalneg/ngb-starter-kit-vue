# NGB Starter Kit Vue Instructions

Use this guide when creating a new Laravel/Vue project from:

```text
https://github.com/nelsonbalneg/ngb-starter-kit-vue
```

## 1. Create A New Project

Clone the starter kit into a new folder:

```bash
git clone https://github.com/nelsonbalneg/ngb-starter-kit-vue.git my-new-app
cd my-new-app
```

If you want the new project to have its own Git history, remove the starter kit Git history and initialize a fresh repository:

```bash
rmdir /s /q .git
git init
git add .
git commit -m "Initial commit from NGB starter kit"
```

For PowerShell, use:

```powershell
Remove-Item -Recurse -Force .git
git init
git add .
git commit -m "Initial commit from NGB starter kit"
```

## 2. Install Dependencies

```bash
composer install
npm install
```

## 3. Create Environment File

Windows Command Prompt:

```bat
copy .env.example .env
```

PowerShell:

```powershell
Copy-Item .env.example .env
```

Linux or macOS:

```bash
cp .env.example .env
```

Then generate the app key:

```bash
php artisan key:generate
```

## 4. Configure `.env`

Update the app name and URL:

```dotenv
APP_NAME="My New App"
APP_URL=http://127.0.0.1:8000
```

Update the database:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=my_new_app
DB_USERNAME=root
DB_PASSWORD=
```

Customize starter seed data before running seeders:

```dotenv
STARTER_PARENT_ORGANIZATION_NAME="My Company"
STARTER_DEFAULT_WORKSPACE_NAME="Main Office"
STARTER_SUPER_ADMIN_EMAIL=admin@mycompany.com
STARTER_DEFAULT_PASSWORD=change-this-password
```

## 5. Run Database Setup

```bash
php artisan migrate:fresh --seed
```

Default seeded users, unless changed in `.env`:

| Role        | Email                   | Password |
| ----------- | ----------------------- | -------- |
| super_admin | super_admin@example.com | password |
| super_admin | admin@example.com       | password |
| user        | user@example.com        | password |
| employee    | employee@example.com    | password |

## 6. Run The App

Start Laravel:

```bash
php artisan serve
```

Start Vite in another terminal:

```bash
npm run dev
```

Open:

```text
http://127.0.0.1:8000
```

## 7. Optional SSO Setup

SSO is disabled by default.

To enable it:

```dotenv
SSO_ENABLED=true
SSO_BASE_URL=https://your-sso-server.test
SSO_USER_URL=https://your-sso-server.test/api/user
SSO_CLIENT_ID=your-client-id
SSO_CLIENT_SECRET=your-client-secret
SSO_REDIRECT_URI="${APP_URL}/auth/callback"
SSO_LOGOUT_REDIRECT_URI="${APP_URL}/dashboard"
SSO_SCOPES="openid profile email"
SSO_STATE_VALIDATION=auto
SSO_DEFAULT_ROLE=employee
SSO_DEFAULT_ORGANIZATION_SLUG=main
```

Never commit real SSO secrets.

## 8. Quality Checks

Run these before pushing changes:

```bash
composer validate --no-check-publish
composer run lint:check
composer run types:check
npm run types:check
```

Optional:

```bash
npm run format:check
php artisan test
```

## 9. Push New Project To GitHub

Create a new GitHub repository, then:

```bash
git remote add origin https://github.com/your-username/my-new-app.git
git branch -M main
git push -u origin main
```

## 10. Starter Kit Rules

- Do not commit `.env`.
- Do not commit real credentials, tokens, or SSO secrets.
- Keep permissions database-driven.
- Do not hardcode role checks.
- Scope roles and permissions by organization/team.
- Use services for business logic.
- Keep UI compact, clean, and enterprise-ready.

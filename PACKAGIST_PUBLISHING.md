# Publish To Packagist

This project is a complete Laravel starter application, so it should be installed with `composer create-project`.

Package name:

```text
ngb/ngb-starter-kit-vue
```

GitHub repository:

```text
https://github.com/nelsonbalneg/ngb-starter-kit-vue
```

## What Is Already Done

The project already has the required `composer.json` fields:

```json
{
    "name": "ngb/ngb-starter-kit-vue",
    "type": "project",
    "description": "Enterprise Laravel, Vue, Inertia, Tailwind, Fortify, and Spatie Teams starter kit."
}
```

You do not need to add a new `require` block for this starter kit. The existing `require` block already lists the Laravel app dependencies.

## 1. Validate Composer

Run this in the starter kit repository:

```bash
composer validate --no-check-publish
```

If you changed `composer.json`, refresh the lock metadata:

```bash
composer update --lock --no-install
```

## 2. Commit And Push

```bash
git add composer.json composer.lock README.md STARTER_KIT_INSTRUCTIONS.md PACKAGIST_PUBLISHING.md
git commit -m "Prepare starter kit for Packagist"
git push origin main
```

## 3. Create A Stable Version Tag

Packagist stable installs need a stable tag.

```bash
git tag v1.0.0
git push origin v1.0.0
```

Without a stable tag, this command will fail:

```bash
composer create-project ngb/ngb-starter-kit-vue my-new-app
```

## 4. Submit To Packagist

1. Log in to Packagist.
2. Open Submit.
3. Enter the repository URL:

```text
https://github.com/nelsonbalneg/ngb-starter-kit-vue
```

4. Submit the package.

Packagist should read the package name from `composer.json` as:

```text
ngb/ngb-starter-kit-vue
```

## 5. Install After Packagist Is Ready

After Packagist crawls the repo and sees `v1.0.0`, this should work:

```bash
composer create-project ngb/ngb-starter-kit-vue my-new-app
```

If Composer says it cannot find the package with stability `stable`, Packagist does not have a stable tag yet. Use the temporary dev install command, or create and push a stable tag.

Then:

```bash
cd my-new-app
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm run dev
```

For PowerShell, use:

```powershell
Copy-Item .env.example .env
```

## Temporary Install Before Packagist Is Ready

Use this while waiting for Packagist setup or while the package only has `dev-main`:

```bash
composer create-project --stability=dev --repository-url=https://github.com/nelsonbalneg/ngb-starter-kit-vue.git ngb/ngb-starter-kit-vue my-new-app dev-main
```

If the package is already visible on Packagist but has no stable tag yet, this shorter dev command can also work:

```bash
composer create-project --stability=dev ngb/ngb-starter-kit-vue my-new-app dev-main
```

## Fix Stability Stable Error

Error:

```text
Could not find package ngb/ngb-starter-kit-vue with stability stable.
```

Cause:

```text
Packagist can see the package, but Composer cannot find a stable version such as v1.0.0.
```

Fix:

```bash
git tag v1.0.0
git push origin v1.0.0
```

Then open the package page on Packagist and click **Update** if it does not crawl automatically.

After Packagist shows version `v1.0.0`, run:

```bash
composer clear-cache
composer create-project ngb/ngb-starter-kit-vue my-new-app
```

## Future Releases

For every new starter-kit release:

```bash
git add .
git commit -m "Update starter kit"
git tag v1.0.1
git push origin main
git push origin v1.0.1
```

Use semantic versions:

- `v1.0.0` first stable release
- `v1.0.1` patch fixes
- `v1.1.0` new backward-compatible features
- `v2.0.0` breaking changes

## Important Notes

- Do not publish secrets in `.env`.
- Keep `.env.example` safe and generic.
- Do not commit `vendor` or `node_modules`.
- `composer require` is for packages installed into an existing project.
- `composer create-project` is for starter kits and full application skeletons.

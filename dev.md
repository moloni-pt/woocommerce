# Local development — Docker, build & run

How to install dependencies, compile assets and run the plugin in a local WordPress + WooCommerce store.

## Prerequisites

* PHP & Composer
* Node.js & npm (only to rebuild CSS/JS)
* Docker & Docker Compose

## 1. PHP dependencies

From the project root:

```bash
composer install
```

`vendor/` is committed (it only holds the Composer autoloader). If you change autoloading, commit the regenerated files.

## 2. Front-end assets (only when you change CSS/JS)

```bash
cd .dev
npm install
npm run build-prod
```

This compiles `.dev/` sources into `assets/`. Rerun it after every CSS/JS change and commit the output.

## 3. Start the store

From the project root:

```bash
docker compose up -d
```

This starts MariaDB and WordPress (official images, PHP 8.2). The project directory is bind-mounted at
`wp-content/plugins/moloni`, so local edits are live.

On the first run a one-shot `setup` container installs WordPress and WooCommerce (currency EUR, base
country Portugal, taxes enabled) and activates the plugin. It exits when done and is skipped on later runs.
Follow it with `docker compose logs -f setup`.

To start again from a clean store: `docker compose down -v`.

## 4. Open the admin

```
http://localhost:8081/wp-admin
```

Log in with `admin` / `123456789`, open **Moloni**, sign in with a Moloni account and select the company.
The first startup can take a few minutes.

## Summary

1. `composer install` (project root)
2. `npm install && npm run build-prod` (inside `.dev`, only after CSS/JS changes)
3. `docker compose up -d` (project root)
4. Open `http://localhost:8081/wp-admin`

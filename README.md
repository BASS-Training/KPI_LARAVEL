<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## GitHub CI/CD

Project ini sudah disiapkan untuk GitHub Actions:

- `CI` di [`.github/workflows/ci.yml`](.github/workflows/ci.yml) akan jalan saat `push` dan `pull_request`.
- `Deploy Production` di [`.github/workflows/deploy.yml`](.github/workflows/deploy.yml) akan jalan saat `push` ke branch `main` atau dijalankan manual lewat `workflow_dispatch`.

### Yang Dicek di CI

- install dependency PHP dan Node.js
- generate Laravel app key
- jalankan `composer test`
- build asset Vite dengan `npm run build`

### Secrets GitHub Yang Wajib Untuk Deploy

Tambahkan secrets berikut di GitHub repository:

- `DEPLOY_HOST`: host server production
- `DEPLOY_PORT`: port SSH, biasanya `22`
- `DEPLOY_USERNAME`: user SSH untuk deploy
- `DEPLOY_SSH_KEY`: private key SSH untuk GitHub Actions
- `DEPLOY_PATH`: root folder app di server, contoh `/var/www/kpi_laravel`
- `DEPLOY_RELOAD_COMMAND`: opsional, misalnya `sudo systemctl reload php8.3-fpm && sudo systemctl reload nginx`

### Struktur Folder Di Server

Workflow deploy memakai struktur release berikut:

- `$DEPLOY_PATH/current`
- `$DEPLOY_PATH/releases/<commit-sha>`
- `$DEPLOY_PATH/shared/.env`
- `$DEPLOY_PATH/shared/storage`

Pastikan file environment production sudah ada di:

```bash
$DEPLOY_PATH/shared/.env
```

### Catatan Deploy

- server target diasumsikan Linux dan sudah terpasang `php`, `composer`, dan service web yang dibutuhkan
- workflow GitHub Actions memakai PHP `8.4` karena `composer.lock` saat ini mengunci paket Symfony yang membutuhkan PHP `>=8.4`
- workflow deploy akan menjalankan `composer install --no-dev`, `php artisan migrate --force`, dan cache ulang konfigurasi Laravel
- workflow menyimpan 5 release terakhir agar rollback/manual switch tetap mudah

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
php artisan migrate
 php artisan optimize:clear
  composer dump-autoload
  php artisan migrate
  npm install
  npm run build

   php artisan cache:clear
  php artisan config:clear
  php artisan route:clear
  php artisan view:clear
  php artisan serve

  npm run dev
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

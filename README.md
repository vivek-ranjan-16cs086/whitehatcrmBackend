<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

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

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Production deployment

The GitHub Actions workflow runs unit tests on pull requests to `main` and deploys pushes to `main` to Hostinger over FTP. Pull requests do not deploy. The deploy workflow can also be started manually from the Actions tab; select `force_composer: true` to reinstall PHP dependencies even when the Composer files have not changed. Only pushes and manual runs initiated by the GitHub user `vivek-ranjan-16cs086` are allowed to deploy.

Before enabling deployment:

1. In Hostinger cPanel, create FTP credentials whose root directory is the Laravel project directory. Set up the website document root to serve the project's `public` directory.
2. Create and configure the production `.env` on the server; do not add it to GitHub. The FTP exclusions intentionally preserve `.env`, `vendor`, runtime storage, and uploaded files already on the server.
3. Ensure the server's SSH shell provides PHP 8.2 or later and Composer. SSH is required whenever `composer.json` or `composer.lock` changes, and for server-side Laravel cache clearing. If `whitehatrealty.in` is not the domain's cPanel path, update the path checks in `.github/workflows/deploy.yml`.
4. Add these values under **Settings → Secrets and variables → Actions** (or as secrets on the `production` environment):

   | Secret or variable | Value |
   | --- | --- |
   | `FTP_SERVER` | Hostinger FTP hostname |
   | `FTP_USERNAME` | FTP username |
   | `FTP_PASSWORD` | FTP password |
   | `SSH_HOST` | Hostinger SSH hostname (required for server-side Composer and cache commands) |
   | `SSH_USERNAME` | cPanel SSH account username |
   | `SSHKEY` or `SSH_PRIVATE_KEY` | Private key authorized in cPanel |
   | `SSH_PASSWORD` | Alternatively, the cPanel SSH password |
   | `SSH_PORT` | SSH port; defaults to `65002` |

`FTP_HOST`, `HOST`, `SSH_KEY`, `USER`, and `SSH_PORT` repository variables are also accepted as compatibility aliases. Composer dependencies are installed on the server only when the Composer manifest or lock file changes, or when forced by a manual run. The workflow does not run database migrations. Keep production `.env` files, credentials, and SSH keys out of the repository.

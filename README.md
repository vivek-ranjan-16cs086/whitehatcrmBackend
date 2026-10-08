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

The GitHub Actions workflow runs the Laravel test suite for pull requests and pushes to `main`. A successful push to `main` also deploys to Hostinger over SSH.

Before enabling deployment:

1. Add this backend as the root of the `whitehatcrmBackend` GitHub repository. The workflow must be at `.github/workflows/deploy.yml` in that repository.
2. In Hostinger cPanel, enable SSH access, authorize a dedicated SSH key for the cPanel account, and clone the GitHub repository with cPanel Git Version Control into the Laravel application directory. That directory must contain `artisan` and `composer.json`, and its `origin` remote must point to the GitHub repository's `main` branch. For a private repository, configure a separate read-only GitHub deploy key on the server so `git pull` can authenticate.
3. Configure the domain's document root to serve the Laravel application's `public` directory. Create and configure the production `.env` on the server; do not add it to GitHub.
4. Ensure the SSH shell provides PHP 8.2 or later, Composer, and Git. Configure the required production PHP extensions and database connection in cPanel.
5. Add these repository secrets under **Settings → Secrets and variables → Actions**:

   | Secret | Value |
   | --- | --- |
   | `HOSTINGER_DEPLOY_PATH` | Absolute path to the cloned Laravel application directory |
   | `HOSTINGER_SSH_HOST` | Hostinger SSH hostname |
   | `HOSTINGER_SSH_KNOWN_HOSTS` | Verified SSH host-key entry for the Hostinger server |
   | `HOSTINGER_SSH_PORT` | Hostinger SSH port |
   | `HOSTINGER_SSH_PRIVATE_KEY` | Private key matching the key authorized in cPanel |
   | `HOSTINGER_SSH_USERNAME` | cPanel SSH account username |

The workflow pulls the latest `main` commit, installs production Composer dependencies, runs database migrations, and refreshes Laravel's optimized caches. It does not run deployments for pull requests. Keep the production `.env`, SSH private key, and database credentials out of the repository.

# Backend Technical Assessment - Ibexa DXP Multi-Locale Implementation

A production-ready multi-market, multi-language Ibexa DXP project with comprehensive content management, deterministic URL routing, and advanced internationalization across Global, Malaysia, and Thailand markets.

## Prerequisites

- [Docker](https://www.docker.com/get-started) (20.10 or later)
- [DDEV](https://ddev.readthedocs.io/en/stable/) (v1.22 or later)
- [Composer](https://getcomposer.org/) (v2.x)
- macOS, Linux, or WSL2 on Windows

## Quick Start - One-Shot Setup

### 1. Clone and Navigate
```bash
cd /Users/imranhisham/Visual\ Code\ Studio\ Project/backend_assessment
```

### 2. Start DDEV
```bash
ddev start
```

This will:
- Start PHP 8.2, Nginx, MariaDB 10.11
- Start Solr 8.11 service on port 8983
- Start Mailhog on ports 1025 (SMTP) and 8025 (Web UI)

### 3. Install Dependencies
```bash
ddev composer install
```

### 4. Configure Environment
Copy the `.env.dist` to `.env` and update if needed:
```bash
cp .env.dist .env
```

**Important**: If you have an Ibexa Experience license, add it to `.env`:
```
IBEXA_LICENSE_KEY=your_license_key_here
```

### 5. Bootstrap the Application
Run the complete setup with a single command:
```bash
ddev composer bootstrap
```

This will:
- Create the database
- Run migrations
- Install Ibexa schema
- Create admin user (login: `admin`, password: `publish123`)
- Load initial fixtures
- Clear caches

### 6. Access the Application

**Multi-Locale Frontend**:
- Global (English): https://backend-assessment.ddev.site/global-en
- Malaysia (English): https://backend-assessment.ddev.site/my-en
- Thailand (English): https://backend-assessment.ddev.site/th-en
- Thailand (Thai): https://backend-assessment.ddev.site/th-th

**Admin/Back-office**: https://backend-assessment.ddev.site/admin  
**Solr Admin**: https://backend-assessment.ddev.site:8983/solr  
**Mailhog UI**: https://backend-assessment.ddev.site:8025

**Default Admin Credentials**:
- Username: `admin`
- Password: `publish123`

## Development Commands

### Composer Scripts

```bash
# Complete setup (DB, migrations, install, bootstrap, cache)
ddev composer bootstrap

# Run tests
ddev composer test

# Code quality checks
ddev composer cs-check      # Check code style
ddev composer cs-fix        # Fix code style
ddev composer psalm         # Run Psalm static analysis
ddev composer phpstan       # Run PHPStan static analysis

# Run all QA tools
ddev composer qa
```

### Symfony Console Commands

```bash
# Clear cache
ddev exec bin/console cache:clear

# Run bootstrap setup
ddev exec bin/console app:bootstrap:setup

# Load fixtures
ddev exec bin/console app:fixtures:load

# Database commands
ddev exec bin/console doctrine:database:create
ddev exec bin/console doctrine:migrations:migrate
```

### DDEV Commands

```bash4
# Start project
ddev start

# Stop project
ddev stop

# SSH into web container
ddev ssh

# View logs
ddev logs

# Restart services
ddev restart
## Project Architecture

### Siteaccesses & Markets
- **global-en**: Global market (English - eng-GB)
- **my-en**: Malaysia market (English - eng-MY)
- **th-en**: Thailand market (English - eng-TH)
- **th-th**: Thailand market (Thai - tha-TH)
- **admin**: Administrative interface

### URL Structure
- Global English: `/global-en/*`
- Malaysia English: `/my-en/*`
- Thailand English: `/th-en/*`
- Thailand Thai: `/th-th/*`

### Language Fallback Chains
- **Global**: eng-GB
- **Malaysia**: eng-MY → eng-GB
- **Thailand**: eng-TH → eng-GB (for English)
- **Thailand**: tha-TH → eng-TH → eng-GB (for Thai)
```

##Project Structure

```
backend_assessment/
│
├── bin/                        # Executables
│   ├── console                # Symfony console
│   └── phpunit                # PHPUnit test runner
│
├── config/                     # Configuration files
│   ├── packages/              # Bundle-specific configs
│   │   ├── framework.yaml     # Symfony framework
│   │   ├── doctrine.yaml      # Database & ORM
│   │   └── ibexa.yaml         # Ibexa configuration
│   ├── routes.yaml            # Routing definitions
│   ├── services.yaml          # Service container
│   └── bundles.php            # Bundle registration
│
├── docs/                       # Documentation
│   ├── ARCHITECTURE.md        # This file
│   ├── CONTENT_MODEL.md       # Content types (Section 2)
│   ├── ROUTING.md             # URL routing (Section 3)
│   └── I18N.md                # Internationalization (Section 4)
│
├── public/                     # Web root (document root)
│   ├── index.php              # Front controller
│   ├── assets/                # Compiled assets
│   └── bundles/               # Bundle assets
│
├── src/                        # Application code
│   ├── Command/               # Console commands
│   │   ├── BootstrapSetupCommand.php
│   │   └── LoadFixturesCommand.php
│   ├── Controller/            # HTTP controllers
│   ├── Entity/                # Doctrine entities
│   ├── EventSubscriber/       # Event listeners
│   ├── Repository/            # Data repositories
│   ├── Service/               # Business logic
│   └── Kernel.php             # Application kernel
│
├── templates/                  # Twig templates
│   ├── themes/                # Frontend themes
│   └── layouts/               # Page layouts
│
├── tests/                      # Test files
│   ├── Unit/                  # Unit tests
│   ├── Functional/            # Functional tests
│   └── Integration/           # Integration tests
│
├── var/                        # Runtime files
│   ├── cache/                 # Application cache
│   ├── log/                   # Log files
│   └── sessions/              # Session data
│
├── vendor/                     # Composer dependencies
│
├── .ddev/                      # DDEV configuration
│   ├── config.yaml            # Main DDEV config
│   ├── docker-compose.solr.yaml   # Solr service
│   └── docker-compose.mailhog.yaml # Mailhog service
│
├── .env                        # Environment variables (local)
├── .env.dist                   # Environment template
├── composer.json               # Dependencies
└── README.md                   # Setup instructions
```
## Common Issues & Solutions

### Issue: "Ibexa license key is missing"
**Solution**: Add your license key to `.env`:
```
IBEXA_LICENSE_KEY=your_license_key_here
```

### Issue: Port conflicts
**Solution**: Check if ports 80, 443, 8983, 8025, or 3306 are in use:
```bash
ddev stop
# Stop conflicting services
ddev start
```

### Issue: Database connection errors
## Testing

### Test Suite Overview
- **98 Service Tests**: LocaleRoutingService, LanguageSwitcherService, RedirectService, UrlAliasGenerator
- **25 Integration Tests**: Locale configuration, siteaccess mapping, URL routing
- **Total Assertions**: 214+ across all test suites

```bash
# Run all tests
ddev composer test

# Run specific test suites
ddev exec bin/phpunit tests/Service/          # Service layer tests (98 tests)
ddev exec bin/phpunit tests/Integration/      # Integration tests (25 tests)
ddev exec bin/phpunit tests/Unit/             # Unit tests

# Run with increased memory (if needed)
ddev exec php -d memory_limit=512M vendor/bin/phpunit

# Run with test output
ddev exec bin/phpunit --testdox

# Run with coverage
ddev exec bin/phpunit --coverage-html var/coverage
```

### Key Test Files
- `tests/Service/LocaleRoutingServiceTest.php` - URL routing logic (41 tests)
- `tests/Service/LanguageSwitcherServiceTest.php` - Language switching (14 tests)
- `tests/Integration/LocaleConfigurationTest.php` - End-to-end locale tests (25 tests)erify Solr is running
ddev exec curl http://solr:8983/solr/admin/ping
## Service URLs

| Service | URL | Notes |
|---------|-----|-------|
| Global (English) | https://backend-assessment.ddev.site/global-en | Global market |
| Malaysia (English) | https://backend-assessment.ddev.site/my-en | Malaysia market |
| Thailand (English) | https://backend-assessment.ddev.site/th-en | Thailand market (English) |
| Thailand (Thai) | https://backend-assessment.ddev.site/th-th | Thailand market (Thai) |
| Admin | https://backend-assessment.ddev.site/admin | Back-office |
| Solr | https://backend-assessment.ddev.site:8983 | Search engine admin |
| Mailhog | https://backend-assessment.ddev.site:8025 | Email testing |
| Database | `db:3306` | Access via `ddev ssh` |
### Issue: Composer install fails
**Solution**: Clear composer cache and retry:
```bash
ddev composer clear-cache
ddev composer install
```

### Issue: Permission errors in var/
**Solution**: Fix permissions:
```bash
ddev exec chmod -R 777 var/
```

## Testing

```bash
# Run all tests
ddev composer test

# Run specific test
ddev exec bin/phpunit tests/Functional/RoutingTest.php

# Run with coverage
ddev exec bin/phpunit --coverage-html var/coverage
```

## Code Quality

This project uses:
- **PHPStan**: Static analysis (level 6)
- **Psalm**: Additional static analysis
- **ECS** (Easy Coding Standard): Code style enforcement
- **PHPUnit**: Unit and functional testing

Run all QA tools:
```bash
ddev composer qa
```

## Service URLs

| Service | URL | Notes |
|---------|-----|-------|
| Frontend | https://backend-assessment.ddev.site | Main site |
## Features Implemented

✅ **Multi-Market Configuration**: Global, Malaysia, Thailand markets  
✅ **Multi-Locale Support**: 4 languages (eng-GB, eng-MY, eng-TH, tha-TH)  
✅ **Deterministic URL Routing**: SEO-friendly URLs with locale prefixes  
✅ **Language Fallback System**: Hierarchical content fallback chains  
✅ **Language Switcher Service**: Seamless language switching  
✅ **URL Alias Generation**: Automated SEO-friendly URL generation  
✅ **Redirect Management**: 301/302 redirect handling  
✅ **Virtual URL Segments**: Content type-specific URL patterns  
✅ **Comprehensive Test Coverage**: 120+ tests with 214+ assertions  
✅ **Code Quality Standards**: PHPStan level 6, Psalm, ECS compliant

## Documentation

Comprehensive documentation available in the `docs/` folder:

- **[ARCHITECTURE.md](docs/ARCHITECTURE.md)**: System architecture and design decisions
- **[CONTENT_MODEL.md](docs/CONTENT_MODEL.md)**: Content types, fields, and structure
- **[ROUTING.md](docs/ROUTING.md)**: URL routing patterns and configuration
- **[I18N.md](docs/I18N.md)**: Internationalization and locale setup
- **[LANGUAGE_SWITCHER.md](docs/LANGUAGE_SWITCHER.md)**: Language switching implementation
APP_ENV=dev                           # Environment (dev/prod)
APP_SECRET=CHANGE_ME                  # Application secret
DATABASE_URL=mysql://db:db@db:3306/db # Database connection
SOLR_DSN=http://solr:8983/solr        # Solr endpoint
SOLR_CORE=collection1                 # Solr core name
MAILER_DSN=smtp://mailhog:1025        # Mail server
SEARCH_ENGINE=solr                    # Search engine type
IBEXA_ADMIN_EMAIL=admin@example.com   # Admin email
IBEXA_ADMIN_PASSWORD=publish123       # Admin password
```

## Next Steps

After successful setup:

1. **Section 2**: Implement content types and page structures
2. **Section 3**: Configure deterministic URL routing
3. **Section 4**: Set up multi-locale siteaccess
4. **Section 5**: Implement locale-specific page sections

See the `docs/` folder for detailed documentation on each section.

## Support

For issues with:
- **DDEV**: Check [DDEV Documentation](https://ddev.readthedocs.io/)
- **Ibexa**: Check [Ibexa Documentation](https://doc.ibexa.co/)
- **This project**: See `docs/` folder or check git commit history

## License

Proprietary - Backend Technical Assessment Project

# Architecture Documentation

## Overview

This Ibexa Experience project is built on Symfony 6.4 and follows a modern PHP architecture with clear separation of concerns, dependency injection, and event-driven patterns.

## Technology Stack

### Core Technologies
- **PHP**: 8.2+
- **Framework**: Symfony 6.4
- **CMS**: Ibexa Experience 4.6
- **Database**: MariaDB 10.11
- **Search**: Apache Solr 8.11
- **Web Server**: Nginx (via DDEV)
- **Container**: DDEV (Docker-based local development)

### Development Tools
- **Composer**: 2.x (dependency management)
- **PHPUnit**: 10.x (testing)
- **Psalm**: 5.x (static analysis)
- **PHPStan**: 1.10+ (static analysis)
- **ECS**: 12.x (code style)
- **Webpack Encore**: Asset management

## Directory Structure

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

## Core Components

### 1. Kernel (`src/Kernel.php`)
- Application bootstrap
- Bundle registration
- Environment configuration
- Uses Symfony's `MicroKernelTrait` for streamlined configuration

### 2. Service Container
Configured in `config/services.yaml`:
- **Autowiring**: Enabled for automatic dependency injection
- **Autoconfiguration**: Automatic service tagging
- **Service location**: All classes in `src/` automatically registered

### 3. Ibexa Repository
The core content management system:
- **Repository**: Main entry point for content operations
- **ContentService**: Create, read, update, delete content
- **LocationService**: Manage content locations in the tree
- **UserService**: User and permission management
- **ContentTypeService**: Content type definitions

### 4. Database Layer
- **Doctrine ORM**: Object-relational mapping
- **Migrations**: Version-controlled schema changes
- **MariaDB**: RDBMS for content and system data

### 5. Search Layer
- **Apache Solr**: Full-text search and indexing
- **Ibexa Solr Bundle**: Integration layer
- **Endpoint**: `http://solr:8983/solr/collection1`

## Environment Variables

Located in `.env` (local) and `.env.dist` (template):

### Critical Variables
```bash
# Application
APP_ENV=dev|prod              # Environment mode
APP_SECRET=random_string      # Security secret

# Database
DATABASE_URL=mysql://...      # Database connection DSN

# Ibexa
IBEXA_LICENSE_KEY=...        # Experience license (required)

# Search
SEARCH_ENGINE=solr           # Search engine type
SOLR_DSN=http://solr:8983    # Solr endpoint
SOLR_CORE=collection1        # Solr core name

# Email
MAILER_DSN=smtp://...        # Mail transport

# Admin
IBEXA_ADMIN_EMAIL=...        # Bootstrap admin email
IBEXA_ADMIN_PASSWORD=...     # Bootstrap admin password
```

## Services Architecture

### DDEV Services

#### Web Container
- **PHP**: 8.2-fpm
- **Nginx**: Latest stable
- **Composer**: 2.x
- **Node.js**: 18.x

#### Database Container
- **MariaDB**: 10.11
- **Port**: 3306 (internal)
- **Credentials**: `db:db` (user:pass)
- **Database**: `db`

#### Solr Container
- **Image**: `solr:8.11`
- **Port**: 8983
- **Core**: `collection1`
- **Memory**: 512MB heap

#### Mailhog Container
- **SMTP**: Port 1025
- **Web UI**: Port 8025
- **Purpose**: Email testing/debugging

## Request Flow

```
Browser Request
    ↓
Nginx (DDEV)
    ↓
public/index.php (Front Controller)
    ↓
Symfony Kernel
    ↓
Routing (URL → Controller)
    ↓
Ibexa View Layer
    ↓
Repository (Content/Location Services)
    ↓
Database (MariaDB) + Search (Solr)
    ↓
Twig Templates
    ↓
Response (HTML)
```

## Content Architecture

### Content Tree
- **Root**: Location ID 2
- **Content**: Organized hierarchically
- **Locations**: Multi-location support for content
- **Sections**: Logical grouping (Media, Editorial, etc.)

### Content Types
Defined via YAML or migrations:
- **Fields**: Text, Rich Text, Image, Relations, etc.
- **Validation**: Field-level constraints
- **Groups**: Logical content type grouping

### Page Builder
- **Blocks**: Reusable content components
- **Layouts**: Page structure templates
- **Zones**: Configurable regions for blocks

## Caching Strategy

### Symfony Cache
- **Development**: File-based, auto-refresh
- **Production**: OpCache + APCu recommended
- **Location**: `var/cache/{env}/`

### HTTP Cache
- **Varnish**: Optional (production)
- **Symfony HTTP Cache**: Built-in reverse proxy

### Ibexa Cache
- **Persistence Cache**: Content/metadata caching
- **HTTP Cache**: View caching
- **Solr Cache**: Search result caching

## Security

### Authentication
- **Ibexa User System**: Repository users
- **Symfony Security**: Firewall configuration
- **Admin Access**: Role-based (`ROLE_ADMIN`)

### Permissions
- **Policies**: Fine-grained access control
- **Limitations**: Context-based restrictions (siteaccess, section, etc.)

## Configuration Management

### Environment-Specific Configs
```
config/
├── packages/               # Default configuration
├── packages/dev/          # Development overrides
├── packages/prod/         # Production overrides
└── packages/test/         # Test overrides
```

### Configuration Loading Order
1. `bundles.php` - Register bundles
2. `packages/*.yaml` - Default bundle configs
3. `packages/{env}/*.yaml` - Environment-specific
4. `services.yaml` - Application services
5. `.env` - Environment variables

## Event System

### Symfony Events
- **Kernel Events**: Request/response lifecycle
- **Console Events**: Command execution
- **Doctrine Events**: Entity lifecycle

### Ibexa Events
- **Content Events**: Publish, update, delete
- **Location Events**: Move, hide, unhide
- **User Events**: Login, logout

Event subscribers in `src/EventSubscriber/`

## Testing Strategy

### Unit Tests (`tests/Unit/`)
- Test individual classes/methods
- Mock dependencies
- Fast execution

### Functional Tests (`tests/Functional/`)
- Test HTTP requests/responses
- Test routing
- Test controllers

### Integration Tests (`tests/Integration/`)
- Test service integration
- Test repository operations
- Test database interactions

## Performance Considerations

### Development
- **XDebug**: Disabled by default (enable via `ddev xdebug on`)
- **Cache**: Auto-refresh on changes
- **Assets**: Uncompiled, source maps enabled

### Production
- **OpCache**: PHP bytecode caching
- **APCu**: User data caching
- **Asset Compilation**: Minified, versioned
- **Doctrine Proxies**: Pre-generated
- **Composer**: `--no-dev --optimize-autoloader`

## Monitoring & Logging

### Logs
- **Location**: `var/log/{env}.log`
- **Rotation**: Automatic (daily)
- **Levels**: DEBUG, INFO, NOTICE, WARNING, ERROR

### Web Profiler (Dev Only)
- **URL**: `/_profiler`
- **Features**: Performance, queries, events, cache

## Deployment Workflow

### Development
```bash
ddev start
ddev composer install
ddev composer bootstrap
```

### Production (Example)
```bash
composer install --no-dev --optimize-autoloader
bin/console cache:clear --env=prod
bin/console cache:warmup --env=prod
bin/console doctrine:migrations:migrate --no-interaction
bin/console ibexa:graphql:generate-schema
```

## Extending the Architecture

### Adding Services
1. Create class in `src/Service/`
2. Use constructor injection for dependencies
3. Service automatically registered (autowiring)

### Adding Commands
1. Create class in `src/Command/`
2. Extend `Symfony\Component\Console\Command\Command`
3. Use `#[AsCommand]` attribute
4. Automatically tagged

### Adding Event Subscribers
1. Create class in `src/EventSubscriber/`
2. Implement `EventSubscriberInterface`
3. Return event => method mapping in `getSubscribedEvents()`

### Adding Content Types
1. Define in YAML: `config/ibexa/content_types.yaml`
2. OR create via migration
3. OR use admin UI

## Best Practices

1. **Use dependency injection** - Never use static calls or global state
2. **Type hint everything** - Leverage PHP 8.2 type system
3. **Follow PSR-12** - Code style consistency
4. **Write tests** - Especially for business logic
5. **Use events** - Decouple components
6. **Cache aggressively** - But invalidate properly
7. **Log appropriately** - Use correct log levels
8. **Environment-specific configs** - Never hardcode

## Troubleshooting

### Check Service Status
```bash
ddev describe
```

### Access Logs
```bash
# Application logs
ddev logs

# Specific service
ddev logs -s solr
```

### Database Access
```bash
ddev mysql
# or
ddev ssh
mysql -u db -p
```

### Clear Everything
```bash
ddev clean
rm -rf var/cache/*
ddev composer clear-cache
ddev restart
```

## Future Enhancements

- CDN integration for assets
- Redis for session storage
- Elasticsearch as search alternative
- GraphQL API layer
- Message queue (Symfony Messenger)
- Multi-region deployment

## References

- [Ibexa Documentation](https://doc.ibexa.co/)
- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [DDEV Documentation](https://ddev.readthedocs.io/)
- [Doctrine ORM](https://www.doctrine-project.org/projects/orm.html)
- [Apache Solr](https://solr.apache.org/guide/)

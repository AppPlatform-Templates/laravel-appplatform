# Laravel on DigitalOcean App Platform

Deploy your Laravel application to DigitalOcean App Platform with production-ready configuration including queues, scheduling, and managed databases.

[![Deploy to DO](https://www.deploytodo.com/do-btn-blue.svg)](https://cloud.digitalocean.com/apps/new?repo=https://github.com/AppPlatform-Templates/laravel-appplatform/tree/main)

## Architecture

```
┌──────────────────────────────────────────────────────────────┐
│                DigitalOcean App Platform                     │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────┐   │
│  │  Web Service    │  │  Queue Worker   │  │  Scheduler  │   │
│  │                 │  │                 │  │             │   │
│  │  Auto-scaling:  │  │  Auto-scaling:  │  │  Fixed: 1   │   │
│  │  min-max        │  │  min-max        │  │  instance   │   │
│  │  instances      │  │  instances      │  │             │   │
│  └────────┬────────┘  └────────┬────────┘  └──────┬──────┘   │
│           │                    │                  │          │
│           └────────────────────┼──────────────────┘          │
│                                │                             │
├────────────────────────────────┼─────────────────────────────┤
│                                │                             │
│    ┌──────────────────┐    ┌─────────────────┐               │
│    │   PostgreSQL     │    │  Redis/Valkey   │               │
│    │  (Managed DB)    │    │   (Managed)     │               │
│    └──────────────────┘    └─────────────────┘               │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

**Components:**
- **Web Service**: Handles HTTP requests with auto-scaling based on CPU usage
- **Queue Worker**: Processes background jobs with auto-scaling based on load
- **Scheduler**: Runs Laravel scheduled tasks (fixed single instance)
- **PostgreSQL**: Managed database for application data
- **Redis/Valkey**: Managed cache and queue backend

## Deployment Methods

### One-Click Deploy

Click the "Deploy to DigitalOcean" button above to deploy instantly.

**Prerequisites (⚠️ MUST DO):**
- Generate application key: `php artisan key:generate --show 2>&1 | grep "^base64:"`
- Set the same `APP_KEY` value for all components when prompted during deployment

### Deploy via CLI

```bash
# Clone your fork or this repository
git clone https://github.com/YOUR_USERNAME/laravel-appplatform.git
cd laravel-appplatform

# Deploy to App Platform
doctl apps create --spec .do/app.yaml

# Set APP_KEY
APP_ID=$(doctl apps list --format ID --no-header | head -1)
APP_KEY=$(php artisan key:generate --show 2>&1 | grep "^base64:")
doctl apps update $APP_ID --env APP_KEY="$APP_KEY"
```

## Quick Start

This template provides the infrastructure configuration for running Laravel on App Platform. Follow these steps to deploy your own Laravel application:

### 1. Fork This Repository

Click the **Fork** button at the top of this repository to create your own copy.

### 2. Add Your Laravel Code

Add your Laravel application files to your forked repository:

- Add your `app/`, `config/`, `database/`, `routes/`, and other Laravel directories
- Add your `composer.json` and Laravel-specific files
- Keep these template files:
  - `.do/` directory (deployment configuration)
  - `Dockerfile` (production setup)
  - `docker-compose.yml` (local development)
  - `.dockerignore`

### 3. Deploy to DigitalOcean

Click the **Deploy to DigitalOcean** button or use the CLI method above.

### 4. Configure APP_KEY

The `APP_KEY` environment variable is required and will be prompted during deployment. Generate one using:

```bash
php artisan key:generate --show 2>&1 | grep "^base64:"
```

Then set it when prompted:
- During deployment, you'll be asked to provide `APP_KEY` for each component (web, queue-worker, scheduler)
- **Use the same generated key for all components**
- The value will be automatically marked as a secret

### 5. Database Setup (Optional)

This template includes PostgreSQL and Redis databases by default. After clicking **Create App**:

**Option 1: Attach Existing Databases**
1. Go to your app → Overview
2. Attach your PostgreSQL and Redis/Valkey databases
3. The app will automatically redeploy

**Option 2: Create New Databases**
1. Create database clusters:
   - PostgreSQL: `doctl databases create laravel-db --engine pg --version 16 --region nyc3 --size db-s-1vcpu-1gb`
   - Redis/Valkey: `doctl databases create laravel-valkey --engine valkey --version 8 --region nyc3 --size db-s-1vcpu-1gb`
2. Attach both databases to your app
3. The app will automatically redeploy

**If you don't need databases**, remove the `databases` section from `.do/deploy.template.yaml` before deploying.

## What's Included

This template creates:

- **Web Service**: Nginx + PHP 8.3 serving your Laravel app
- **Queue Worker**: Processes background jobs using Laravel queues
- **Scheduler**: Runs Laravel scheduled tasks every minute
- **PostgreSQL Database**: Managed database for your data (optional)
- **Redis/Valkey**: For caching and queue management (optional)

All components are production-optimized with caching, health checks, and auto-scaling support.

**Template Information:**
- Based on Laravel 11.x with PHP 8.3
- PostgreSQL 16 and Redis 7
- Docker configuration for all services
- Production-ready Nginx and PHP-FPM configuration
- Auto-scaling support

## Pricing

For detailed pricing information based on instance sizes and resources, visit the [DigitalOcean App Platform Pricing](https://www.digitalocean.com/pricing/app-platform) page.

## Deployment Modes

### Production Mode (Default)

Full setup with web service, queue worker, scheduler, PostgreSQL, and Redis.

See [.do/deploy.template.yaml](.do/deploy.template.yaml) for configuration.

### Starter Mode

Simplified setup for smaller apps with reduced infrastructure.

See [.do/examples/README.md](.do/examples/README.md) for starter mode configuration.

## Local Development

Test your setup locally with Docker:

```bash
# Start containers
docker compose up -d

# Run migrations
docker compose exec web php artisan migrate
```

Visit `http://localhost:8080`

**Note**: The local environment uses environment variables from `docker-compose.yml` instead of a `.env` file. All necessary configuration is pre-set for local development.

## Common Tasks

### Run Database Migrations

From the App Platform console:
1. Go to your app → Console
2. Select the `web` component
3. Run: `php artisan migrate`

Or use `doctl`:
```bash
doctl apps create-deployment <app-id> --exec-command "php artisan migrate"
```

### Configure File Storage (Spaces)

To use DigitalOcean Spaces for file uploads:

1. Create a Spaces bucket in your DigitalOcean account
2. Generate Spaces access keys (API → Spaces Keys)
3. Add environment variables in App Platform:
   ```
   FILESYSTEM_DISK=s3
   AWS_ACCESS_KEY_ID=your-key
   AWS_SECRET_ACCESS_KEY=your-secret
   AWS_BUCKET=your-bucket-name
   AWS_DEFAULT_REGION=nyc3
   AWS_ENDPOINT=https://nyc3.digitaloceanspaces.com
   ```

### View Logs

```bash
# All components
doctl apps logs <app-id> --follow

# Specific component
doctl apps logs <app-id> --component web --follow
```

## Environment Variables Reference

Key variables you need to set:

| Variable | Description | Required |
|----------|-------------|----------|
| `APP_KEY` | Laravel encryption key (generate with `php artisan key:generate --show 2>&1 \| grep "^base64:"`) | Yes |
| `APP_ENV` | Environment (production recommended) | Yes |
| `APP_DEBUG` | Debug mode (false for production) | Yes |
| `DB_*` | Database credentials (auto-injected when database attached) | If using DB |
| `REDIS_*` | Redis credentials (auto-injected when Redis attached) | If using Redis |
| `FILESYSTEM_DISK` | Storage driver (local or s3 for Spaces) | No |
| `AWS_*` | Spaces credentials (if using DigitalOcean Spaces) | If using Spaces |

## Troubleshooting

**App won't start?**
- Ensure `APP_KEY` is set in environment variables
- Check that databases are attached and running (if using databases)
- Review build logs: `doctl apps logs <app-id> --type build`

**Queue jobs not processing?**
- Verify Redis is connected
- Check queue-worker logs: `doctl apps logs <app-id> --component queue-worker`

**Database connection errors?**
- Confirm PostgreSQL database is attached
- Verify database credentials in App Platform settings
- Ensure database is in the same region as your app

**500 errors?**
- Check app logs for detailed error messages
- Verify all environment variables are set correctly
- Ensure `APP_DEBUG=false` in production

## Resources

- [Laravel Documentation](https://laravel.com/docs)
- [DigitalOcean App Platform Documentation](https://docs.digitalocean.com/products/app-platform/)
- [App Spec Reference](https://docs.digitalocean.com/products/app-platform/reference/app-spec/)
- [Alternative Configurations](.do/examples/README.md)

## Support

- **Template Issues**: [GitHub Issues](https://github.com/AppPlatform-Templates/laravel-appplatform/issues)
- **App Platform Docs**: [DigitalOcean Documentation](https://docs.digitalocean.com/products/app-platform/)
- **Laravel Help**: [Laravel Community](https://laravel.com/community)

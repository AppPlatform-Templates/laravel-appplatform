# Laravel on DigitalOcean App Platform

[![Deploy to DO](https://www.deploytodo.com/do-btn-blue.svg)](https://cloud.digitalocean.com/apps/new?repo=https://github.com/AppPlatform-Templates/laravel-appplatform/tree/main)

Deploy your Laravel application to DigitalOcean App Platform in minutes with production-ready configuration including queues, scheduling, and managed databases.

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                DigitalOcean App Platform                     │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────┐ │
│  │  Web Service    │  │  Queue Worker   │  │  Scheduler  │ │
│  │  (Nginx + PHP)  │  │  (Background)   │  │  (Worker)   │ │
│  │                 │  │                 │  │             │ │
│  │  Auto-scaling:  │  │  Auto-scaling:  │  │  Fixed: 1   │ │
│  │  min-max        │  │  min-max        │  │  instance   │ │
│  │  instances      │  │  instances      │  │             │ │
│  └────────┬────────┘  └────────┬────────┘  └──────┬──────┘ │
│           │                    │                   │         │
│           └────────────────────┼───────────────────┘         │
│                                │                             │
├────────────────────────────────┼─────────────────────────────┤
│                                │                             │
│  ┌──────────────────┐    ┌─────────────────┐               │
│  │   PostgreSQL     │    │  Redis/Valkey   │               │
│  │  (Managed DB)    │    │   (Managed)     │               │
│  │   Version 16     │    │   Version 8     │               │
│  └──────────────────┘    └─────────────────┘               │
│                                                               │
└───────────────────────────────────────────────────────────────┘
```

**Components:**
- **Web Service**: Handles HTTP requests with auto-scaling based on CPU usage
- **Queue Worker**: Processes background jobs with auto-scaling based on load
- **Scheduler**: Runs Laravel scheduled tasks (fixed single instance)
- **PostgreSQL**: Managed database for application data
- **Redis/Valkey**: Managed cache and queue backend

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

#### Option 1
1. Click the **Deploy to DigitalOcean** button at the top of your forked repository's README

#### Option 2
1. Go to [DigitalOcean App Platform](https://cloud.digitalocean.com/apps)
2. Click **Create App**
3. Connect your forked repository
4. App Platform will detect the `.do/deploy.template.yaml` configuration
5. Click **Next** through the setup

### 4. Configure Your App

Before creating the app, you must set the `APP_KEY` environment variable:

1. Generate an application key:
   ```bash
   php artisan key:generate --show
   ```

2. Set the `APP_KEY` as an **app-level environment variable** in App Platform:
   - Go to your app → App-Level Environment Variables
   - Click "Edit" or "Add Variable"
   - Add `APP_KEY` with the value from step 1
   - Click "Save"
  
3. Click **Create App**

### 5. Set Up Databases (If Using Databases)

This template includes PostgreSQL and Redis databases by default. If you want to use databases, you must attach them after clicking on "Create App". If you don't need databases, remove the `databases` section from `.do/deploy.template.yaml` before deploying.

**If you're using databases**, choose one of these options:

#### Option 1: Create Databases First (Recommended)
1. Create databases before or during app creation:
   - PostgreSQL database cluster (recommended: Basic plan, 1GB RAM)
   - Redis database cluster (recommended: Basic plan, 1GB RAM)
2. After clicking **Create App**, go to your app → Overview
3. Attach both databases to your app
4. The app will automatically redeploy

#### Option 2: Create Databases After App Creation
1. Click **Create App** (app will fail to deploy without databases)
2. Create database clusters:
   - PostgreSQL database cluster (recommended: Basic plan, 1GB RAM)
   - Redis/Valkey database cluster (recommended: Basic plan, 1GB RAM)
3. Go to your app → Overview
4. Attach both databases to your app
5. The app will automatically redeploy

That's it! Your Laravel app is now live on DigitalOcean.

## What Gets Deployed

This template creates:

- **Web Service**: Nginx + PHP 8.3 serving your Laravel app
- **Queue Worker**: Processes background jobs using Laravel queues
- **Scheduler**: Runs Laravel scheduled tasks every minute
- **PostgreSQL Database**: Managed database for your data (optional)
- **Redis**: For caching and queue management (optional)

All components are production-optimized with caching, health checks, and auto-scaling support.

## Local Development

Test your setup locally with Docker:

```bash
docker compose up -d
docker compose exec web php artisan migrate
docker compose exec web php artisan key:generate
```

Visit `http://localhost:8080`

## Deployment Modes

### Production Mode (Default)
Full setup with web service, queue worker, scheduler, PostgreSQL, and Redis.

**Cost**: ~$40-60/month depending on instance sizes

### Starter Mode
Simplified setup for smaller apps with reduced infrastructure.

**Cost**: ~$17-22/month

See [.do/examples/README.md](.do/examples/README.md) for starter mode configuration.

## Template Information

This template is based on **Laravel 11.x** with PHP 8.3, PostgreSQL 16, and Redis 7.

**What's included:**
- Docker configuration (web, queue worker, scheduler)
- App Platform deployment specs (`.do/deploy.template.yaml`)
- Production-ready Nginx and PHP-FPM configuration
- Auto-scaling support

**What you need to add:**
- Your Laravel application code (`app/`, `config/`, `routes/`, etc.)
- Your `composer.json` with dependencies
- Your views and assets

See [Alternative Configurations](./.do/examples/README.md) for starter mode and other deployment options.

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

## Environment Variables Reference

Key variables you need to set:

| Variable | Description | Required |
|----------|-------------|----------|
| `APP_KEY` | Laravel encryption key (generate with `php artisan key:generate --show`) | Yes |
| `APP_ENV` | Environment (production recommended) | Yes |
| `APP_DEBUG` | Debug mode (false for production) | Yes |
| `DB_*` | Database credentials (auto-injected when database attached) | If using DB |
| `REDIS_*` | Redis credentials (auto-injected when Redis attached) | If using Redis |
| `FILESYSTEM_DISK` | Storage driver (local or s3 for Spaces) | No |
| `AWS_*` | Spaces credentials (if using DigitalOcean Spaces) | If using Spaces |

## Support

- Template Issues: [GitHub Issues](https://github.com/AppPlatform-Templates/laravel-appplatform/issues)
- App Platform Docs: [DigitalOcean Documentation](https://docs.digitalocean.com/products/app-platform/)
- Laravel Help: [Laravel Community](https://laravel.com/community)

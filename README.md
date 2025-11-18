# Laravel on DigitalOcean App Platform

[![Deploy to DO](https://www.deploytodo.com/do-btn-blue.svg)](https://cloud.digitalocean.com/apps/new?repo=https://github.com/AppPlatform-Templates/laravel-appplatform/tree/main)

Production-ready Laravel template for DigitalOcean App Platform with auto-scaling, managed databases, and queue processing.

> **Note**: This is a **configuration template** providing Docker and App Platform setup for Laravel. You'll need to add your Laravel application files. See [TEMPLATE_USAGE.md](./TEMPLATE_USAGE.md) for instructions.

## Features

- **Production-Ready Architecture**: Multi-component setup with web service, queue worker, and scheduler
- **Auto-Scaling**: Automatic scaling for web and queue worker components based on CPU usage
- **Managed Services**: PostgreSQL and Redis managed databases included
- **Queue Processing**: Background job processing with Laravel queues
- **Task Scheduling**: Laravel scheduler runs every minute via App Platform's scheduled jobs
- **Object Storage**: Ready for DigitalOcean Spaces integration for file uploads
- **Health Checks**: Built-in health endpoints for monitoring
- **Optimized Docker**: Multi-stage builds with PHP 8.3, Nginx, and production optimizations

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                DigitalOcean App Platform                     │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌─────────────┐      ┌──────────────┐   ┌──────────────┐  │
│  │ Web Service │      │Queue Worker  │   │  Scheduler   │  │
│  │(Nginx+PHP)  │      │(Background)  │   │  (Worker)    │  │
│  │Auto: 2-10   │      │Auto: 1-5     │   │  Fixed: 1    │  │
│  └──────┬──────┘      └──────┬───────┘   └──────┬───────┘  │
│         │                    │                   │           │
│         └────────────────────┼───────────────────┘           │
│                              │                               │
├──────────────────────────────┼───────────────────────────────┤
│                              │                               │
│  ┌────────────────┐    ┌─────────────┐                      │
│  │   PostgreSQL   │    │    Redis    │                      │
│  │  (Managed DB)  │    │  (Managed)  │                      │
│  │    Version 16  │    │  Version 7  │                      │
│  └────────────────┘    └─────────────┘                      │
│                                                               │
└───────────────────────────────────────────────────────────────┘
```

## Deployment Modes

This template offers two deployment configurations:

### Production Mode (Default) - $152-440/month

**Best for**: Production workloads, high-traffic applications, SaaS platforms

- ✅ Auto-scaling web service (2-10 instances)
- ✅ Auto-scaling queue worker (1-5 instances)
- ✅ Scheduler worker
- ✅ Managed PostgreSQL (production)
- ✅ Managed Redis

**Deploy**: Click the button above or see [Manual Deployment](#manual-deployment)

### Starter Mode - $17-22/month

**Best for**: Development, testing, small applications, learning

- ✅ Single web instance
- ✅ Optional scheduler
- ✅ Dev Database (PostgreSQL)
- ❌ No Redis (uses database for cache)
- ❌ No queue worker (sync jobs)

**Deploy**: See [.do/examples/README.md](.do/examples/README.md)

| Feature | Starter | Production |
|---------|---------|------------|
| Web instances | 1 | 2-10 (auto-scale) |
| Queue worker | No (sync) | Yes (1-5 auto-scale) |
| Database | Dev (1GB) | Managed (scalable) |
| Redis | No | Yes |
| Cost/month | $17-22 | $152-440 |

[**Compare all features →**](.do/examples/README.md)

---

## Quick Deploy (Production Mode)

Click the Deploy to DigitalOcean button above to deploy this Laravel application in minutes.

### What Gets Created

- **Web Service**: 2-10 instances (auto-scaling), serves HTTP traffic
- **Queue Worker**: 1-5 instances (auto-scaling), processes background jobs
- **Scheduler**: 1 instance (Worker), runs Laravel scheduled tasks every minute
- **PostgreSQL Database**: Managed database for application data
- **Redis**: Managed cache and queue backend

### Required Configuration

After deployment, you'll need to set:

1. **APP_KEY**: Generate with `php artisan key:generate --show`
   - Set this in the App Platform environment variables

All other variables are automatically configured by App Platform's managed services.

## Manual Deployment

### Prerequisites

- DigitalOcean account
- `doctl` CLI installed and configured
- Git repository (GitHub, GitLab, or public Git)

### Step 1: Fork or Clone

```bash
git clone https://github.com/AppPlatform-Templates/laravel-appplatform.git
cd laravel-appplatform
```

### Step 2: Generate Application Key

```bash
# Locally (requires PHP and Composer)
composer install
php artisan key:generate --show
```

Copy the generated key (format: `base64:...`) for Step 4.

### Step 3: Create the App

```bash
# Using the deploy template
doctl apps create --spec .do/deploy.template.yaml
```

Or use the GitHub-connected version:

```bash
# Update .do/app.yaml with your repository
doctl apps create --spec .do/app.yaml
```

### Step 4: Set Environment Variables

```bash
# Get your app ID
APP_ID=$(doctl apps list --format ID --no-header | head -1)

# Set the APP_KEY you generated in Step 2
doctl apps update $APP_ID --update-env-var APP_KEY="base64:YOUR_KEY_HERE"
```

### Step 5: Monitor Deployment

```bash
# Watch deployment progress
doctl apps list

# Get app URL
doctl apps get $APP_ID --format DefaultIngress
```

Visit the URL to see your Laravel application!

## Configuration

### Environment Variables

All required environment variables are pre-configured in the App Spec. The main ones you may customize:

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `APP_KEY` | Encryption key | None | **Yes** |
| `APP_NAME` | Application name | `Laravel on App Platform` | No |
| `APP_ENV` | Environment | `production` | No |
| `APP_DEBUG` | Debug mode | `false` | No |
| `FILESYSTEM_DISK` | Storage driver | `local` | No |

### Database Configuration

Database credentials are automatically injected by App Platform:

- `DB_HOST`: `${db.HOSTNAME}`
- `DB_PORT`: `${db.PORT}`
- `DB_DATABASE`: `${db.DATABASE}`
- `DB_USERNAME`: `${db.USERNAME}`
- `DB_PASSWORD`: `${db.PASSWORD}`

### Redis Configuration

Redis credentials are automatically injected:

- `REDIS_HOST`: `${redis.HOSTNAME}`
- `REDIS_PORT`: `${redis.PORT}`
- `REDIS_PASSWORD`: `${redis.PASSWORD}`

### File Storage (Optional)

To use DigitalOcean Spaces for file uploads:

1. Create a Spaces bucket:
```bash
doctl spaces create laravel-storage --region nyc3
```

2. Create access credentials:
```bash
doctl spaces access create
```

3. Update environment variables in App Platform:
```bash
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-spaces-key
AWS_SECRET_ACCESS_KEY=your-spaces-secret
AWS_BUCKET=laravel-storage
AWS_DEFAULT_REGION=nyc3
AWS_ENDPOINT=https://nyc3.digitaloceanspaces.com
```

## Development

### Local Development with Docker

```bash
# Build and run locally
docker compose up -d

# Run migrations
docker compose exec web php artisan migrate

# Generate app key
docker compose exec web php artisan key:generate
```

### Local Development with Laravel Sail

```bash
# Install dependencies
composer install

# Start Sail
./vendor/bin/sail up -d

# Run migrations
./vendor/bin/sail artisan migrate

# Generate app key
./vendor/bin/sail artisan key:generate
```

## Components

### Web Service

- **Technology**: Nginx + PHP-FPM 8.3
- **Scaling**: 2-10 instances (70% CPU threshold)
- **Instance Size**: `professional-xs` ($24/month per instance)
- **Health Check**: `/` endpoint
- **Purpose**: Serves HTTP requests, dispatches jobs to queue

### Queue Worker

- **Technology**: PHP CLI running `php artisan queue:work`
- **Scaling**: 1-5 instances (75% CPU threshold)
- **Instance Size**: `professional-xs` ($24/month per instance)
- **Purpose**: Processes background jobs from Redis queue
- **Configuration**:
  - `--tries=3`: Retry failed jobs 3 times
  - `--timeout=90`: Max 90 seconds per job
  - `--max-jobs=1000`: Restart after 1000 jobs
  - `--max-time=3600`: Restart after 1 hour

### Scheduler

- **Technology**: PHP CLI running `php artisan schedule:run`
- **Component Type**: Worker (continuous loop, not SCHEDULED job)
- **Frequency**: Every 60 seconds
- **Instance Size**: `basic-xxs` ($5/month)
- **Purpose**: Runs Laravel's task scheduler

> **Note**: The scheduler runs as a **Worker** (not a SCHEDULED job) because Laravel's scheduler needs to run every minute, but App Platform's scheduled jobs have a minimum interval of 15 minutes. The Worker runs a continuous loop that executes `schedule:run` every 60 seconds.

## Estimated Costs

| Component | Configuration | Monthly Cost |
|-----------|--------------|--------------|
| Web Service | 2-10 instances × $24 | $48-$240 |
| Queue Worker | 1-5 instances × $24 | $24-$120 |
| Scheduler | 1 instance × $5 | $5 |
| PostgreSQL | Managed, production | $60+ |
| Redis | Managed, production | $15+ |
| **Total (Minimum)** | | **~$152/month** |
| **Total (Scaled)** | | **~$440/month** |

Costs scale based on traffic and auto-scaling configuration.

## Performance Optimization

### PHP Optimizations Included

- **Opcache**: Pre-configured for production with validation disabled
- **Config Caching**: Runs `php artisan config:cache` during build
- **Route Caching**: Runs `php artisan route:cache` during build
- **View Caching**: Runs `php artisan view:cache` during build
- **Composer**: Uses `--optimize-autoloader --no-dev`

### Nginx Optimizations

- Gzip compression enabled
- Static file caching (1 year expiry)
- FastCGI buffering configured
- Worker connections optimized

### Recommended Additional Optimizations

- Enable Cloudflare or App Platform CDN for static assets
- Implement Redis caching for database queries
- Use Laravel Horizon for queue monitoring
- Set up log forwarding for better observability

## Monitoring

### Built-in Health Checks

- **Web Service**: `GET /` (configured in App Spec)
- **PHP-FPM**: `GET /fpm-ping` (internal)
- **PHP-FPM Status**: `GET /fpm-status` (internal, localhost only)

### App Platform Metrics

Monitor in the App Platform dashboard:
- CPU usage per component
- Memory usage per component
- HTTP request rate and latency
- Deployment history and logs

### Recommended Monitoring Tools

- **Laravel Horizon**: Queue dashboard (install separately)
- **Laravel Telescope**: Debugging tool (development only)
- **External Monitoring**: UptimeRobot, Pingdom, or Datadog
- **APM**: New Relic, Scout APM

## Troubleshooting

### App Won't Start

1. Check APP_KEY is set: `doctl apps list-env-vars <app-id>`
2. Review deployment logs: `doctl apps logs <app-id> --type build`
3. Verify database migrations ran successfully

### Queue Jobs Not Processing

1. Check queue worker logs: `doctl apps logs <app-id> --component queue-worker`
2. Verify Redis connection: Check Redis environment variables
3. Ensure jobs are being dispatched: `php artisan queue:work --once`

### Database Connection Errors

1. Verify database is running: `doctl databases list`
2. Check database credentials in environment variables
3. Ensure trusted sources include App Platform (configured automatically)

### 500 Errors

1. Check application logs: `doctl apps logs <app-id>`
2. Verify `APP_DEBUG=false` (errors won't show details in production)
3. Check storage permissions are set correctly
4. Ensure all caches are cleared

## Additional Resources

- **[Getting Started Guide](./GETTING_STARTED.md)** - Step-by-step tutorial to build and deploy a Laravel app
- [Deployment Guide](./DEPLOYMENT_GUIDE.md) - Comprehensive deployment documentation
- [Alternative Configurations](./.do/examples/README.md) - Starter mode and other options
- [Template Usage](./TEMPLATE_USAGE.md) - How to use this template
- [Version Info](./VERSION.md) - Laravel version and update policy
- [Laravel Documentation](https://laravel.com/docs)
- [App Platform Documentation](https://docs.digitalocean.com/products/app-platform/)
- [App Spec Reference](https://docs.digitalocean.com/products/app-platform/reference/app-spec/)

## Support

- **Template Issues**: [GitHub Issues](https://github.com/AppPlatform-Templates/laravel-appplatform/issues)
- **App Platform Support**: [DigitalOcean Support](https://www.digitalocean.com/support)
- **Laravel Questions**: [Laravel Community](https://laravel.com/community)

## License

This template is open-sourced software licensed under the [MIT license](LICENSE).

Laravel framework is a trademark of Taylor Otwell. This template is not officially endorsed by or affiliated with the Laravel project.

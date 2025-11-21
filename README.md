# Laravel on DigitalOcean App Platform

[![Deploy to DO](https://www.deploytodo.com/do-btn-blue.svg)](https://cloud.digitalocean.com/apps/new?repo=https://github.com/AppPlatform-Templates/laravel-appplatform/tree/main)

Production-ready Laravel template for DigitalOcean App Platform with auto-scaling, managed databases and queue processing.

> **Note**: This is a **configuration template** providing Docker and App Platform setup for Laravel. You'll need to add your Laravel application files. See [TEMPLATE_USAGE.md](./TEMPLATE_USAGE.md) for instructions.

## Features

- **Production-Ready Architecture**: Multi-component setup with web service, queue worker, and scheduler
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
│  │  1 instance │      │  1 instance  │   │  1 instance  │  │
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

### Production Mode (Default)

**Best for**: Production workloads, high-traffic applications, SaaS platforms

- ✅ Web service (scalable)
- ✅ Queue worker (scalable)
- ✅ Scheduler worker
- ✅ Managed PostgreSQL (production)
- ✅ Managed Redis/Valkey

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
| Web instances | 1 | 1+ (scalable) |
| Queue worker | No (sync) | Yes (scalable) |
| Database | Dev (1GB) | Managed (scalable) |
| Redis/Valkey | No | Yes |

[**Compare all features →**](.do/examples/README.md)

---

## Quick Deploy (Production Mode)

Click the Deploy to DigitalOcean button above to deploy this Laravel application.

### Important: Database Setup

The Deploy button creates the app but **does not automatically provision databases**. You have two options:

#### Option 1: Create Databases First (Recommended)
1. Create a PostgreSQL database cluster in DigitalOcean
2. Create a Redis/Valkey database cluster in DigitalOcean
3. Click the Deploy button
4. During app creation, attach the existing databases

#### Option 2: Create App First, Attach Later
1. Click the Deploy button
2. Create the app (it will fail to connect to databases initially)
3. Create PostgreSQL and Redis/Valkey database clusters
4. Attach the databases to your app in App Platform settings
5. Redeploy the app

### What Gets Created

- **Web Service**: 1 instance, serves HTTP traffic
- **Queue Worker**: 1 instance, processes background jobs
- **Scheduler**: 1 instance (Worker), runs Laravel scheduled tasks every minute

### Required Configuration

After deployment, you'll need to set:

1. **APP_KEY**: Generate with `php artisan key:generate --show`
   - Set this in the App Platform environment variables

All other variables are automatically configured when databases are attached.

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

1. **Create a Spaces bucket** using one of these methods:
   - **DigitalOcean Console**: Navigate to Spaces and create a new bucket
   - **s3cmd**: `s3cmd mb s3://laravel-storage`
   - **AWS CLI**: `aws s3 mb s3://laravel-storage --endpoint-url https://nyc3.digitaloceanspaces.com`

   > **Note**: `doctl` does not support Spaces bucket management. Use the console, s3cmd, or AWS CLI.

2. **Create access credentials** in the DigitalOcean Console:
   - Go to API → Spaces Keys → Generate New Key

3. **Update environment variables** in App Platform:
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
- **Scaling**: Manual (can be scaled in App Platform settings)
- **Instance Size**: `apps-d-1vcpu-0.5gb`
- **Health Check**: `/health` endpoint
- **Purpose**: Serves HTTP requests, dispatches jobs to queue

### Queue Worker

- **Technology**: PHP CLI running `php artisan queue:work`
- **Scaling**: Manual (can be scaled in App Platform settings)
- **Instance Size**: `apps-d-1vcpu-0.5gb`
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
- **Instance Size**: `apps-d-1vcpu-0.5gb`
- **Purpose**: Runs Laravel's task scheduler

> **Note**: The scheduler runs as a **Worker** (not a SCHEDULED job) because Laravel's scheduler needs to run every minute, but App Platform's scheduled jobs have a minimum interval of 15 minutes. The Worker runs a continuous loop that executes `schedule:run` every 60 seconds.

## Estimated Costs

Costs depend on instance sizes and database tiers you choose. See [DigitalOcean App Platform Pricing](https://www.digitalocean.com/pricing/app-platform) for current rates.

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

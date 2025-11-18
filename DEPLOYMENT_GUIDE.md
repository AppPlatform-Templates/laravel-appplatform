# Laravel on DigitalOcean App Platform

## Overview

Laravel is a modern PHP web application framework with expressive, elegant syntax. This guide provides production-ready deployment configurations for running scalable Laravel applications on DigitalOcean App Platform.

## Architecture Analysis

### Components

Laravel applications typically consist of:

**Critical Components** (Required for basic functionality):
- **Web Service**: Laravel application (Nginx + PHP-FPM) serving HTTP requests
- **Database**: PostgreSQL for application data
- **Cache**: Redis for session management and caching

**Recommended Components** (Production best practices):
- **Queue Worker**: Background job processing (`php artisan queue:work`)
- **Scheduler**: Cron job runner (`php artisan schedule:run`) for scheduled tasks
- **Redis**: Queue backend for asynchronous job processing

### Data Dependencies

- **Database**: PostgreSQL 15+ - Stores application data, users, sessions (if not using Redis)
- **Cache/Queue**: Redis - Session storage, application cache, queue backend for jobs
- **Object Storage**: DigitalOcean Spaces (optional) - File uploads, backups, large assets

### Communication Patterns

```
User Request → Web Service (Nginx + PHP-FPM)
                     ↓
              PostgreSQL Database
                     ↓
            Redis (Cache/Sessions)
                     ↓
              Queue Jobs → Queue Worker (Background)
                     ↓
              Scheduled Tasks → Scheduler (Cron)
```

- Web service handles HTTP requests, dispatches jobs to queue
- Queue worker processes jobs asynchronously from Redis queue
- Scheduler runs Laravel's task scheduler every minute
- All components share database and Redis connections

## Feasibility Assessment

✅ **App Platform Suitability**: Highly Suitable

Laravel is an excellent fit for App Platform with proper configuration:

**Why it works well:**
- Stateless application layer (scales horizontally)
- Standard HTTP/HTTPS serving (perfect for Services)
- Queue workers ideal for Worker components
- Scheduler fits App Platform's scheduled jobs
- Native support for PostgreSQL and Redis
- Ephemeral filesystem works with Spaces for file uploads

**Considerations:**
- File uploads should use DigitalOcean Spaces (S3-compatible)
- Session storage should use Redis (not file-based)
- Cache should use Redis (not file-based)
- Logs can use App Platform's logging or forward to external service

---

## 🏗️ Production Mode

**Goal**: Scalable, resilient, production-ready deployment for high-traffic applications

**Architecture**:
- Web Service with auto-scaling (2-10 instances)
- Queue Worker with auto-scaling (1-5 instances)
- Scheduler (scheduled job, runs every minute)
- Managed PostgreSQL (production with HA)
- Managed Redis (production)
- DigitalOcean Spaces for file storage

**Use Cases**:
- Production workloads
- High-traffic applications (1000+ requests/min)
- Multi-tenant SaaS platforms
- E-commerce applications
- Applications requiring high availability

### App Spec

```yaml
spec:
  name: laravel-production
  region: nyc3

  services:
    - name: web
      github:
        repo: <your-username>/laravel-appplatform
        branch: main
        deploy_on_push: true
      dockerfile_path: docker/web/Dockerfile
      http_port: 80
      instance_count: 2
      instance_size_slug: professional-xs
      autoscaling:
        min_instance_count: 2
        max_instance_count: 10
        metrics:
          cpu:
            percent: 70
      health_check:
        http_path: /health
        initial_delay_seconds: 60
        period_seconds: 10
        timeout_seconds: 5
        success_threshold: 1
        failure_threshold: 3
      envs:
        - key: APP_NAME
          value: "Laravel on App Platform"
        - key: APP_ENV
          value: "production"
        - key: APP_DEBUG
          value: "false"
        - key: APP_URL
          value: "${APP_URL}"
        - key: APP_KEY
          scope: RUN_AND_BUILD_TIME
          type: SECRET
          value: "${APP_KEY}"
        - key: DB_CONNECTION
          value: "pgsql"
        - key: DB_HOST
          value: "${db.HOSTNAME}"
        - key: DB_PORT
          value: "${db.PORT}"
        - key: DB_DATABASE
          value: "${db.DATABASE}"
        - key: DB_USERNAME
          value: "${db.USERNAME}"
        - key: DB_PASSWORD
          value: "${db.PASSWORD}"
          scope: RUN_AND_BUILD_TIME
          type: SECRET
        - key: REDIS_HOST
          value: "${redis.HOSTNAME}"
        - key: REDIS_PORT
          value: "${redis.PORT}"
        - key: REDIS_PASSWORD
          value: "${redis.PASSWORD}"
          scope: RUN_AND_BUILD_TIME
          type: SECRET
        - key: CACHE_DRIVER
          value: "redis"
        - key: SESSION_DRIVER
          value: "redis"
        - key: QUEUE_CONNECTION
          value: "redis"
        - key: FILESYSTEM_DISK
          value: "s3"
        - key: AWS_ACCESS_KEY_ID
          scope: RUN_AND_BUILD_TIME
          type: SECRET
          value: "${SPACES_ACCESS_KEY}"
        - key: AWS_SECRET_ACCESS_KEY
          scope: RUN_AND_BUILD_TIME
          type: SECRET
          value: "${SPACES_SECRET_KEY}"
        - key: AWS_DEFAULT_REGION
          value: "nyc3"
        - key: AWS_BUCKET
          value: "${SPACES_BUCKET_NAME}"
        - key: AWS_ENDPOINT
          value: "https://nyc3.digitaloceanspaces.com"
        - key: AWS_USE_PATH_STYLE_ENDPOINT
          value: "false"

  workers:
    - name: queue-worker
      github:
        repo: <your-username>/laravel-appplatform
        branch: main
        deploy_on_push: true
      dockerfile_path: docker/queue-worker/Dockerfile
      instance_count: 1
      instance_size_slug: professional-xs
      autoscaling:
        min_instance_count: 1
        max_instance_count: 5
        metrics:
          cpu:
            percent: 75
      envs:
        - key: APP_ENV
          value: "production"
        - key: APP_KEY
          scope: RUN_TIME
          type: SECRET
          value: "${APP_KEY}"
        - key: DB_CONNECTION
          value: "pgsql"
        - key: DB_HOST
          value: "${db.HOSTNAME}"
        - key: DB_PORT
          value: "${db.PORT}"
        - key: DB_DATABASE
          value: "${db.DATABASE}"
        - key: DB_USERNAME
          value: "${db.USERNAME}"
        - key: DB_PASSWORD
          scope: RUN_TIME
          type: SECRET
          value: "${db.PASSWORD}"
        - key: REDIS_HOST
          value: "${redis.HOSTNAME}"
        - key: REDIS_PORT
          value: "${redis.PORT}"
        - key: REDIS_PASSWORD
          scope: RUN_TIME
          type: SECRET
          value: "${redis.PASSWORD}"
        - key: QUEUE_CONNECTION
          value: "redis"

  jobs:
    - name: scheduler
      kind: SCHEDULED
      schedule: "*/1 * * * *"  # Every minute
      github:
        repo: <your-username>/laravel-appplatform
        branch: main
        deploy_on_push: true
      dockerfile_path: docker/scheduler/Dockerfile
      instance_count: 1
      instance_size_slug: basic-xxs
      envs:
        - key: APP_ENV
          value: "production"
        - key: APP_KEY
          scope: RUN_TIME
          type: SECRET
          value: "${APP_KEY}"
        - key: DB_CONNECTION
          value: "pgsql"
        - key: DB_HOST
          value: "${db.HOSTNAME}"
        - key: DB_PORT
          value: "${db.PORT}"
        - key: DB_DATABASE
          value: "${db.DATABASE}"
        - key: DB_USERNAME
          value: "${db.USERNAME}"
        - key: DB_PASSWORD
          scope: RUN_TIME
          type: SECRET
          value: "${db.PASSWORD}"
        - key: REDIS_HOST
          value: "${redis.HOSTNAME}"
        - key: REDIS_PORT
          value: "${redis.PORT}"
        - key: REDIS_PASSWORD
          scope: RUN_TIME
          type: SECRET
          value: "${redis.PASSWORD}"

databases:
  - engine: PG
    name: db
    production: true
    version: "15"
  - engine: REDIS
    name: redis
    production: true
    version: "7"
```

### Managed Services Setup

```bash
# 1. Create managed PostgreSQL
doctl databases create laravel-prod-db \
  --engine pg \
  --region nyc3 \
  --size db-s-2vcpu-4gb \
  --version 15

# Get connection details
DB_ID=$(doctl databases list --format ID,Name --no-header | grep laravel-prod-db | awk '{print $1}')
doctl databases connection $DB_ID

# 2. Create managed Redis
doctl databases create laravel-redis \
  --engine redis \
  --region nyc3 \
  --size db-s-1vcpu-1gb \
  --version 7

# Get connection details
REDIS_ID=$(doctl databases list --format ID,Name --no-header | grep laravel-redis | awk '{print $1}')
doctl databases connection $REDIS_ID

# 3. Create Spaces bucket for file storage
doctl spaces create laravel-storage --region nyc3

# 4. Create Spaces access credentials
doctl spaces access create

# 5. Deploy the app
doctl apps create --spec .do/app.yaml
```

### Environment Variables

| Variable | Description | Example Value | Required |
|----------|-------------|---------------|----------|
| `APP_NAME` | Application name | `Laravel on App Platform` | Yes |
| `APP_ENV` | Environment | `production` | Yes |
| `APP_KEY` | Encryption key (generate with `php artisan key:generate`) | `base64:xxxx...` | Yes |
| `APP_DEBUG` | Debug mode | `false` | Yes |
| `APP_URL` | Application URL | `https://your-app.ondigitalocean.app` | Yes |
| `DB_CONNECTION` | Database driver | `pgsql` | Yes |
| `DB_HOST` | Database hostname | `${db.HOSTNAME}` | Yes |
| `DB_PORT` | Database port | `${db.PORT}` | Yes |
| `DB_DATABASE` | Database name | `${db.DATABASE}` | Yes |
| `DB_USERNAME` | Database username | `${db.USERNAME}` | Yes |
| `DB_PASSWORD` | Database password | `${db.PASSWORD}` | Yes |
| `REDIS_HOST` | Redis hostname | `${redis.HOSTNAME}` | Yes |
| `REDIS_PORT` | Redis port | `${redis.PORT}` | Yes |
| `REDIS_PASSWORD` | Redis password | `${redis.PASSWORD}` | Yes |
| `CACHE_DRIVER` | Cache driver | `redis` | Yes |
| `SESSION_DRIVER` | Session driver | `redis` | Yes |
| `QUEUE_CONNECTION` | Queue driver | `redis` | Yes |
| `FILESYSTEM_DISK` | Default filesystem | `s3` | Recommended |
| `AWS_ACCESS_KEY_ID` | Spaces access key | `your-spaces-key` | If using Spaces |
| `AWS_SECRET_ACCESS_KEY` | Spaces secret key | `your-spaces-secret` | If using Spaces |
| `AWS_DEFAULT_REGION` | Spaces region | `nyc3` | If using Spaces |
| `AWS_BUCKET` | Spaces bucket name | `laravel-storage` | If using Spaces |
| `AWS_ENDPOINT` | Spaces endpoint | `https://nyc3.digitaloceanspaces.com` | If using Spaces |
| `AWS_USE_PATH_STYLE_ENDPOINT` | Path style endpoint | `false` | If using Spaces |

### Storage Configuration

**DigitalOcean Spaces Setup**:

1. Create Spaces bucket:
```bash
doctl spaces create laravel-storage --region nyc3
```

2. Create access credentials:
```bash
doctl spaces access create
```

3. Configure Laravel filesystem (`config/filesystems.php`):
```php
's3' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'),
    'endpoint' => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
],
```

4. Set `FILESYSTEM_DISK=s3` in environment variables

### Scaling Configuration

**Auto-scaling Rules**:

**Web Service:**
- Min instances: 2 (high availability)
- Max instances: 10 (handles traffic spikes)
- CPU threshold: 70%
- Instance size: professional-xs ($24/month per instance)

**Queue Worker:**
- Min instances: 1 (cost-effective baseline)
- Max instances: 5 (handles job spikes)
- CPU threshold: 75%
- Instance size: professional-xs ($24/month per instance)

**Scheduler:**
- Fixed: 1 instance (no scaling needed)
- Instance size: basic-xxs ($5/month)

**Instance Sizing Guidance**:
- `basic-xxs` ($5): Testing only
- `basic-xs` ($10): Very light production
- `professional-xs` ($24): Standard production (recommended)
- `professional-s` ($48): High-traffic production
- `professional-m` ($96): Very high-traffic production

### Estimated Monthly Cost

| Component | Configuration | Cost Range |
|-----------|--------------|------------|
| Web Service (2-10 instances, professional-xs) | App | $48-240 |
| Queue Worker (1-5 instances, professional-xs) | App | $24-120 |
| Scheduler (1 instance, basic-xxs) | App | $5 |
| Managed PostgreSQL (db-s-2vcpu-4gb) | Database | $60 |
| Managed Redis (db-s-1vcpu-1gb) | Database | $15 |
| Spaces (250GB storage, 1TB transfer) | Storage | $5 |
| Bandwidth | Network | Variable |
| **Total** | | **~$157-445/month** |

Note: Base cost starts at ~$157/month, scales up to ~$445/month under high load with auto-scaling.

### High Availability Setup

- **Multiple instances**: 2+ web service instances for redundancy
- **Managed database**: PostgreSQL with automatic backups and failover
- **Health checks**: Configured on `/health` endpoint
- **Graceful shutdown**: Laravel handles SIGTERM signals properly
- **Database connection pooling**: Managed PostgreSQL includes pgBouncer

### Monitoring

**Built-in Metrics** (App Platform Dashboard):
- CPU usage per component
- Memory usage per component
- Request rate and latency
- HTTP status codes
- Deployment history

**Recommended Additional Monitoring**:
- **Laravel Telescope**: Development/debugging (disable in production)
- **Laravel Horizon**: Queue monitoring dashboard
- **Log Forwarding**: Send logs to Datadog, Logtail, or Papertrail
- **Application Performance Monitoring**: New Relic, Scout APM
- **Uptime Monitoring**: UptimeRobot, Pingdom

---

## Pre-Deployment Checklist

### Application Preparation

- [ ] Generate application key: `php artisan key:generate`
- [ ] Configure database connection for PostgreSQL
- [ ] Configure cache driver for Redis
- [ ] Configure session driver for Redis
- [ ] Configure queue connection for Redis
- [ ] Configure filesystem for Spaces (S3-compatible)
- [ ] Set up health check endpoint (`/health`)
- [ ] Test application locally with PostgreSQL and Redis
- [ ] Run migrations: `php artisan migrate`
- [ ] Optimize for production:
  - [ ] `php artisan config:cache`
  - [ ] `php artisan route:cache`
  - [ ] `php artisan view:cache`

### Infrastructure Preparation

- [ ] Create managed PostgreSQL database
- [ ] Create managed Redis instance
- [ ] Create Spaces bucket for file uploads
- [ ] Create Spaces access credentials
- [ ] Configure trusted sources for databases (optional)
- [ ] Set up environment variables in App Platform

### Security Checklist

- [ ] Set `APP_DEBUG=false` in production
- [ ] Use strong `APP_KEY` (generated securely)
- [ ] Use environment variables for all secrets
- [ ] Enable database SSL connections
- [ ] Configure trusted sources for database access
- [ ] Set up CORS if building API
- [ ] Configure rate limiting
- [ ] Review Laravel security best practices

---

## Troubleshooting

### Common Issues

**Issue**: Database connection failures
**Solution**:
- Verify `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` environment variables
- Check database trusted sources settings
- Ensure database is in same region as app
- Test connection: `php artisan migrate --pretend`

**Issue**: Redis connection failures
**Solution**:
- Verify `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD` environment variables
- Check Redis trusted sources settings
- Test cache: `php artisan cache:clear`
- Test queue: `php artisan queue:work --once`

**Issue**: Queue jobs not processing
**Solution**:
- Verify queue worker is running (check worker logs)
- Check `QUEUE_CONNECTION=redis` is set
- Verify Redis connection
- Check job failures: `php artisan queue:failed`
- Restart queue worker after deployment

**Issue**: Scheduler not running
**Solution**:
- Verify scheduled job is configured correctly
- Check cron expression: `*/1 * * * *` (every minute)
- Review scheduler logs
- Test locally: `php artisan schedule:run`

**Issue**: File upload failures
**Solution**:
- Verify Spaces credentials are set correctly
- Check `FILESYSTEM_DISK=s3` is set
- Test connection: `php artisan tinker` then `Storage::disk('s3')->put('test.txt', 'test');`
- Verify Spaces bucket permissions

**Issue**: 500 errors after deployment
**Solution**:
- Check application logs in App Platform
- Verify `APP_KEY` is set
- Ensure all required environment variables are configured
- Check database migrations have run
- Clear caches: `php artisan config:clear`, `php artisan cache:clear`

**Issue**: Slow performance
**Solution**:
- Enable opcache in PHP configuration
- Use `professional` tier instances (dedicated CPU)
- Implement query optimization
- Use Redis for caching
- Enable Laravel's cache optimization commands
- Consider CDN for static assets

---

## Additional Resources

- **Laravel Documentation**: https://laravel.com/docs
- **App Platform Documentation**: https://docs.digitalocean.com/products/app-platform/
- **Laravel on App Platform Guide**: https://docs.digitalocean.com/developer-center/deploy-a-laravel-app-to-app-platform/
- **Internal Routing**: https://docs.digitalocean.com/products/app-platform/how-to/manage-internal-routing/
- **Log Forwarding**: https://docs.digitalocean.com/products/app-platform/how-to/forward-logs/
- **Managed Databases**: https://docs.digitalocean.com/products/databases/
- **Spaces Object Storage**: https://docs.digitalocean.com/products/spaces/

---

## Performance Optimization Tips

### PHP Configuration

Optimize `php.ini` for production:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.fast_shutdown=1
```

### Laravel Optimization

Production optimization commands (run in Dockerfile):
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
composer install --optimize-autoloader --no-dev
```

### Database Optimization

- Use database connection pooling (built into Managed PostgreSQL)
- Index frequently queried columns
- Use eager loading to avoid N+1 queries
- Implement database query caching with Redis
- Monitor slow queries with Laravel Debugbar (development only)

### Caching Strategy

- **Config caching**: `php artisan config:cache`
- **Route caching**: `php artisan route:cache`
- **View caching**: `php artisan view:cache`
- **Query caching**: Use Laravel's cache facade
- **Full page caching**: Consider Cloudflare or Laravel cache middleware

### Queue Optimization

- Use separate queue workers for different job types
- Implement job batching for bulk operations
- Set appropriate timeouts and retry counts
- Monitor failed jobs and implement alerting
- Use Laravel Horizon for queue insights

---

## Feedback and Improvements

This deployment guide provides a production-ready Laravel configuration for DigitalOcean App Platform. For issues, questions, or improvements, please open an issue in the repository.

# Laravel App Platform Configurations

This folder contains alternative App Platform configurations for different use cases.

## Available Configurations

### 1. Production Mode (Default)

**Files**: `../.do/deploy.template.yaml` and `../.do/app.yaml`

**Use for**: Production workloads, high-traffic applications, multi-tenant SaaS

**Architecture**:
- ✅ Web service (auto-scaling 1-3 instances)
- ✅ Queue worker (auto-scaling 1-3 instances)
- ✅ Scheduler worker (1 instance)
- ✅ Managed PostgreSQL (production)
- ✅ Managed Redis/Valkey (for cache, sessions, queue)

**Features**:
- Auto-scaling based on CPU usage
- High availability (multiple instances)
- Background job processing
- Task scheduling every minute
- Redis/Valkey for performance
- Production-ready instance sizes

**When to use**:
- Production applications
- High-traffic applications
- Need for high availability
- Background job processing required

---

### 2. Starter Mode

**File**: `examples/starter.app.yaml`

**Use for**: Development, testing, small applications, learning

**Architecture**:
- ✅ Web service (1 instance, no auto-scaling)
- ✅ Scheduler worker (1 instance, optional)
- ✅ Dev Database (PostgreSQL)
- ❌ No Redis (uses database for cache/sessions)
- ❌ No queue worker (jobs run synchronously)

**Features**:
- Single web instance
- Development database (1GB limit)
- Database-backed cache and sessions
- Synchronous job processing
- Optional scheduler support

**When to use**:
- Development and testing
- Learning Laravel + App Platform
- Small personal projects
- Low-traffic applications

**Limitations**:
- Single instance (no redundancy)
- No auto-scaling
- Jobs run synchronously (slower for background tasks)
- Database cache is slower than Redis
- 1GB database limit

---

## How to Deploy

### Deploy Starter Mode

1. **Using the Deploy-to-DO button**:

   Not available for examples (use manual deployment below).

2. **Manual deployment with doctl**:

```bash
# Deploy Starter Mode
doctl apps create --spec .do/examples/starter.app.yaml

# Get app ID
APP_ID=$(doctl apps list --format ID --no-header | head -1)

# Set APP_KEY
php artisan key:generate --show
doctl apps update $APP_ID --env APP_KEY="base64:YOUR_KEY_HERE"

# Get app URL
doctl apps get $APP_ID --format DefaultIngress --no-header
```

### Deploy Production Mode

See the main [README.md](../../README.md) for production deployment instructions.

---

## Comparison Table

| Feature | Starter Mode | Production Mode |
|---------|-------------|-----------------|
| **Web Service** | 1 instance | 1-3 instances (auto-scale) |
| **Queue Worker** | None (sync) | 1-3 instances (auto-scale) |
| **Scheduler** | Optional | Included |
| **Database** | Dev (1GB) | Managed (scalable) |
| **Redis/Valkey** | None | Managed |
| **Cache Driver** | Database | Redis/Valkey |
| **Session Driver** | Database | Redis/Valkey |
| **Queue Driver** | Sync | Redis/Valkey |
| **Instance Size** | apps-d-1vcpu-0.5gb | apps-d-1vcpu-0.5gb |
| **High Availability** | No | Yes (multiple instances) |
| **Auto-scaling** | No | Yes |

---

## Migrating from Starter to Production

When your app outgrows Starter mode:

### 1. Update App Spec

Replace `.do/examples/starter.app.yaml` with `../.do/app.yaml`(For doctl apps create mode) or with `../.do/deploy.template.yaml`(For Deploy To DigitalOcean mode)

### 2. Migrate Database

```bash
# Backup data from dev database
pg_dump $DEV_DB_CONNECTION > backup.sql

# Create managed PostgreSQL
doctl databases create my-app-db --engine pg --region nyc3 --size db-s-2vcpu-4gb

# Restore to managed database
psql $PROD_DB_CONNECTION < backup.sql
```

### 3. Add Redis/Valkey

```bash
# Create managed Valkey (or Redis)
doctl databases create my-app-valkey --engine valkey --region nyc3
```

### 4. Update Environment Variables

Update app configuration:
- `CACHE_DRIVER=redis`
- `SESSION_DRIVER=redis`
- `QUEUE_CONNECTION=redis`
- Add Redis/Valkey connection details

### 5. Redeploy

```bash
doctl apps update $APP_ID --spec ../.do/deploy.template.yaml
```

---

## Customizing Configurations

### Remove Scheduler from Starter Mode

Edit `starter.app.yaml` and remove the `workers:` section if you don't need scheduled tasks:

```yaml
# Remove this entire section if not needed
workers:
  - name: scheduler
    # ...
```

### Add Spaces to Starter Mode

For file uploads, add environment variables:

```yaml
- key: FILESYSTEM_DISK
  value: "s3"
- key: AWS_ACCESS_KEY_ID
  scope: RUN_AND_BUILD_TIME
  type: SECRET
- key: AWS_SECRET_ACCESS_KEY
  scope: RUN_AND_BUILD_TIME
  type: SECRET
# etc.
```

### Optimize Production Configuration

1. **Lower minimum instances**: Already optimized at `min_instance_count: 1`
2. **Remove scheduler**: Remove if you don't need scheduled tasks
3. **Use dev database**: Only for development/testing environments

---

## Need Help?

- **Template Issues**: [GitHub Issues](https://github.com/AppPlatform-Templates/laravel-appplatform/issues)
- **App Platform Docs**: https://docs.digitalocean.com/products/app-platform/

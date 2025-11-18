# Setup Guide for Laravel on App Platform

This template provides the Docker configuration and App Platform setup for running Laravel. You need to add your Laravel application files to this template.

## Option 1: Use This Template for Existing Laravel App

If you have an existing Laravel application:

1. **Copy your Laravel files** to this directory:
   ```bash
   # Copy everything except docker/, .do/, and Docker-related files
   cp -r /path/to/your/laravel-app/* .
   ```

2. **Keep the template's Docker configuration**:
   - `.do/` directory (App Platform configuration)
   - `docker/` directory (Dockerfiles and configs)
   - `DEPLOYMENT_GUIDE.md`
   - `README.md`
   - `VERSION.md`

3. **Update your `.env.example`** to match the template's `.env.example` for App Platform compatibility

4. **Test locally**:
   ```bash
   docker compose up -d
   docker compose exec web php artisan migrate
   ```

5. **Deploy to App Platform** following the README instructions

## Option 2: Start with Fresh Laravel Installation

If you're starting a new Laravel project:

1. **Install Laravel** using Composer:
   ```bash
   # Remove existing files (keep Docker config)
   rm -rf composer.json package.json .env.example

   # Create new Laravel project in current directory
   composer create-project laravel/laravel .
   ```

2. **Restore the template configuration**:
   ```bash
   # Restore .env.example with App Platform variables
   git checkout .env.example

   # Ensure Docker files are preserved
   git checkout docker/
   git checkout .do/
   ```

3. **Update `composer.json`** to include `predis/predis` for Redis:
   ```bash
   composer require predis/predis
   ```

4. **Configure Laravel for App Platform**:
   - Update `.env.example` with the App Platform environment variables
   - Ensure `config/database.php` supports PostgreSQL
   - Ensure `config/cache.php` and `config/session.php` support Redis

5. **Test and deploy** following the README instructions

## Option 3: Clone and Customize Template Application

This template includes minimal Laravel structure. To make it a complete application:

1. **Initialize Laravel**:
   ```bash
   # Install dependencies
   composer install
   npm install

   # Generate application key
   php artisan key:generate

   # Run migrations
   php artisan migrate

   # Build assets
   npm run build
   ```

2. **Add your application code**:
   - Create controllers in `app/Http/Controllers/`
   - Create models in `app/Models/`
   - Create views in `resources/views/`
   - Add routes in `routes/web.php`

3. **Test locally** with Docker:
   ```bash
   docker compose up -d
   ```

4. **Deploy to App Platform** when ready

## Important Files for App Platform

These files are pre-configured for App Platform and should not be removed:

### App Platform Configuration
- `.do/deploy.template.yaml` - Deploy-to-DO button configuration
- `.do/app.yaml` - Example App Spec for manual deployment

### Docker Configuration
- `docker/web/Dockerfile` - Web service (Nginx + PHP-FPM)
- `docker/web/nginx.conf` - Nginx main configuration
- `docker/web/default.conf` - Nginx server block
- `docker/web/php.ini` - PHP production settings
- `docker/web/www.conf` - PHP-FPM pool configuration
- `docker/web/supervisord.conf` - Supervisor (manages Nginx + PHP-FPM)
- `docker/queue-worker/Dockerfile` - Queue worker service
- `docker/queue-worker/php.ini` - Queue worker PHP settings
- `docker/scheduler/Dockerfile` - Scheduler service
- `docker/scheduler/php.ini` - Scheduler PHP settings

### Documentation
- `README.md` - Main documentation with Deploy-to-DO button
- `DEPLOYMENT_GUIDE.md` - Comprehensive deployment guide
- `VERSION.md` - Version tracking
- `SETUP.md` - This file

## Required Laravel Configuration for App Platform

Ensure your Laravel application has these configurations:

### 1. Database Configuration (`config/database.php`)

```php
'pgsql' => [
    'driver' => 'pgsql',
    'url' => env('DATABASE_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8',
    'prefix' => '',
    'prefix_indexes' => true,
    'schema' => 'public',
    'sslmode' => 'prefer',
],
```

### 2. Redis Configuration (`config/database.php`)

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'predis'),
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_DB', 0),
    ],
    'cache' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_CACHE_DB', 1),
    ],
],
```

### 3. Cache Configuration (`config/cache.php`)

```php
'default' => env('CACHE_STORE', 'redis'),

'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
],
```

### 4. Session Configuration (`config/session.php`)

```php
'driver' => env('SESSION_DRIVER', 'redis'),
'connection' => 'default',
```

### 5. Queue Configuration (`config/queue.php`)

```php
'default' => env('QUEUE_CONNECTION', 'redis'),

'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
],
```

### 6. Filesystem Configuration (`config/filesystems.php`)

For DigitalOcean Spaces support:

```php
'disks' => [
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
],
```

### 7. Logging Configuration (`config/logging.php`)

For App Platform compatibility (logs to stderr):

```php
'default' => env('LOG_CHANNEL', 'stderr'),

'channels' => [
    'stderr' => [
        'driver' => 'monolog',
        'handler' => StreamHandler::class,
        'formatter' => env('LOG_STDERR_FORMATTER'),
        'with' => [
            'stream' => 'php://stderr',
        ],
    ],
],
```

## Testing Before Deployment

1. **Test Docker build**:
   ```bash
   docker build -f docker/web/Dockerfile -t laravel-web .
   docker build -f docker/queue-worker/Dockerfile -t laravel-queue .
   docker build -f docker/scheduler/Dockerfile -t laravel-scheduler .
   ```

2. **Test locally with docker compose**:
   ```bash
   docker compose up -d
   docker compose logs -f
   ```

3. **Run migrations**:
   ```bash
   docker compose exec web php artisan migrate
   ```

4. **Test queue worker**:
   ```bash
   # Dispatch a test job
   docker compose exec web php artisan tinker
   # In tinker: dispatch(new App\Jobs\TestJob());

   # Check queue worker logs
   docker compose logs queue-worker
   ```

5. **Test scheduler**:
   ```bash
   docker compose exec scheduler php artisan schedule:run
   ```

## Next Steps

Once your Laravel application is set up and tested locally:

1. Push to GitHub repository
2. Deploy to App Platform using the Deploy-to-DO button or `doctl`
3. Set `APP_KEY` environment variable in App Platform
4. Monitor deployment and check logs
5. Run migrations if needed
6. Test your application

For detailed deployment instructions, see [README.md](./README.md) and [DEPLOYMENT_GUIDE.md](./DEPLOYMENT_GUIDE.md).

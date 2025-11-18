# Template Usage Instructions

This is a **Docker and App Platform configuration template** for Laravel applications. It contains the infrastructure setup but requires a Laravel application to function.

## What's Included

✅ **Docker Configuration**
- Multi-component Docker setup (Web, Queue Worker, Scheduler)
- Production-optimized PHP 8.3 + Nginx configuration
- Multi-stage builds for minimal image sizes

✅ **App Platform Configuration**
- `.do/deploy.template.yaml` for Deploy-to-DO button
- `.do/app.yaml` for manual deployment
- Auto-scaling configuration
- Managed database integration

✅ **Documentation**
- Comprehensive deployment guide
- Setup instructions
- Cost estimates
- Troubleshooting tips

## What's NOT Included

❌ **Laravel Application Files**

This template does NOT include a complete Laravel application. You need to add:

- `app/` directory with your application code
- `config/` directory with Laravel configuration
- `routes/` files
- `resources/views/` templates
- `database/migrations/` files
- And all other Laravel application files

## How to Use This Template

### Method 1: Add Your Laravel App (Recommended)

1. **Clone this template**:
   ```bash
   git clone https://github.com/AppPlatform-Templates/laravel-appplatform.git my-laravel-app
   cd my-laravel-app
   ```

2. **Copy your Laravel application files**:
   ```bash
   # Copy your Laravel app files (excluding vendor/, node_modules/)
   cp -r /path/to/your/laravel-app/{app,config,database,public,resources,routes} .
   cp /path/to/your/laravel-app/{composer.json,package.json,.env.example} .
   ```

3. **Update environment variables**:
   - Merge the template's `.env.example` with yours
   - Ensure App Platform variables (`${db.HOSTNAME}`, etc.) are included

4. **Test locally**:
   ```bash
   composer install
   npm install
   npm run build
   docker compose up -d
   ```

5. **Deploy to App Platform** using the README instructions

### Method 2: Install Fresh Laravel

1. **Clone this template**:
   ```bash
   git clone https://github.com/AppPlatform-Templates/laravel-appplatform.git my-laravel-app
   cd my-laravel-app
   ```

2. **Remove placeholder files and install Laravel**:
   ```bash
   # Remove skeleton files
   rm -rf app/ config/ database/ public/ resources/ routes/
   rm composer.json package.json

   # Install Laravel
   composer create-project laravel/laravel temp-laravel
   mv temp-laravel/* temp-laravel/.* .
   rmdir temp-laravel
   ```

3. **Restore Docker/App Platform configs** (git will show what changed):
   ```bash
   git checkout .do/ docker/ DEPLOYMENT_GUIDE.md README.md SETUP.md VERSION.md
   ```

4. **Configure for App Platform**:
   - Update `.env.example` with App Platform variables
   - Install `predis/predis`: `composer require predis/predis`
   - Update configs to use Redis for cache/session/queue

5. **Test and deploy**

### Method 3: Fork and Use as Reference

If you just want the Docker/App Platform configuration:

1. **Fork or clone the template**
2. **Copy the Docker and .do/ directories** to your existing Laravel project
3. **Adapt as needed** for your specific requirements

## Key Files for App Platform

These files are configured for DigitalOcean App Platform and are the main value of this template:

- `.do/deploy.template.yaml` - Deploy-to-DO configuration
- `.do/app.yaml` - Manual deployment spec
- `docker/web/Dockerfile` - Web service container
- `docker/queue-worker/Dockerfile` - Queue worker container
- `docker/scheduler/Dockerfile` - Scheduler container
- All configuration files in `docker/*/` directories

## Next Steps

1. Read [SETUP.md](./SETUP.md) for detailed setup instructions
2. Read [README.md](./README.md) for deployment instructions
3. Read [DEPLOYMENT_GUIDE.md](./DEPLOYMENT_GUIDE.md) for comprehensive documentation

## Need Help?

- Template issues: https://github.com/AppPlatform-Templates/laravel-appplatform/issues
- Laravel docs: https://laravel.com/docs
- App Platform docs: https://docs.digitalocean.com/products/app-platform/

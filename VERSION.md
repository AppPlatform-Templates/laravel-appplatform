# Version Information

## Laravel Version

This template is based on **Laravel 11.x** - the latest major version of Laravel.

- **Laravel Framework**: ^11.0
- **PHP Version**: ^8.2 (using PHP 8.3 in Docker images)
- **PostgreSQL**: 16 (configurable)
- **Redis**: 7 (configurable)

## Update Policy

This template follows Laravel's release cycle:

- **Major versions**: Updated when Laravel releases a new major version
- **Minor versions**: Updated for important features and improvements
- **Patch versions**: Updated for security fixes
- **Dependencies**: Kept up to date with Laravel's requirements

## Checking for Updates

To check if there's a newer version of Laravel available:

```bash
composer outdated laravel/framework
```

To update Laravel and dependencies:

```bash
composer update
```

## DigitalOcean App Platform Compatibility

This template is optimized for DigitalOcean App Platform and uses:

- App Platform's managed databases (PostgreSQL + Redis)
- Auto-scaling for web and queue worker components
- Scheduled jobs for Laravel's task scheduler
- Production-ready PHP and Nginx configurations

## Changelog

### Version 1.0.0 (Initial Release)
- Laravel 11.x support
- Production-ready Docker configuration
- Multi-component architecture (Web, Queue Worker, Scheduler)
- PostgreSQL and Redis integration
- Auto-scaling support
- Health check endpoints
- DigitalOcean Spaces integration for file storage
- Comprehensive deployment documentation

## Upstream Repository

This template is maintained by the DigitalOcean App Platform team.

- **Template Repository**: https://github.com/AppPlatform-Templates/laravel-appplatform
- **Laravel Framework**: https://github.com/laravel/laravel
- **Laravel Documentation**: https://laravel.com/docs

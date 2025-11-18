# Getting Started with Laravel on App Platform

This guide walks you through creating a simple Laravel application and deploying it to DigitalOcean App Platform from scratch.

## Prerequisites

- **DigitalOcean account** with billing enabled
- **doctl** CLI installed and configured: https://docs.digitalocean.com/reference/doctl/how-to/install/
- **Docker** and **Docker Compose** installed (for local testing)
- **PHP 8.2+** and **Composer** installed locally
- **Node.js 18+** and **npm** installed
- **Git** installed
- Basic Laravel knowledge

## Step 1: Clone the Template

```bash
# Clone the template
git clone https://github.com/AppPlatform-Templates/laravel-appplatform.git my-laravel-app
cd my-laravel-app

# Remove git history (you'll create your own repo later)
rm -rf .git
```

## Step 2: Install Laravel

Install a fresh Laravel application into the template:

```bash
# Install Laravel dependencies
composer create-project laravel/laravel temp-laravel

# Move Laravel files into the template (preserving Docker/AppPlatform configs)
cp -r temp-laravel/app temp-laravel/bootstrap temp-laravel/config \
      temp-laravel/database temp-laravel/public temp-laravel/resources \
      temp-laravel/routes temp-laravel/tests .

# Update composer.json (keep template's version but add Laravel's dependencies)
cp temp-laravel/composer.json .

# Clean up
rm -rf temp-laravel

# Install Predis for Redis support
composer require predis/predis

# Install frontend dependencies
npm install
```

## Step 3: Configure for App Platform

Update your `.env.example` with App Platform-compatible settings:

```bash
cat > .env.example << 'EOF'
APP_NAME="My Laravel App"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=https://your-app.ondigitalocean.app

LOG_CHANNEL=stderr
LOG_LEVEL=info

# Database (PostgreSQL on App Platform)
DB_CONNECTION=pgsql
DB_HOST=${db.HOSTNAME}
DB_PORT=${db.PORT}
DB_DATABASE=${db.DATABASE}
DB_USERNAME=${db.USERNAME}
DB_PASSWORD=${db.PASSWORD}

# Cache, Session, Queue (Redis)
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis (Managed Redis on App Platform)
REDIS_CLIENT=predis
REDIS_HOST=${redis.HOSTNAME}
REDIS_PASSWORD=${redis.PASSWORD}
REDIS_PORT=${redis.PORT}

# Filesystem
FILESYSTEM_DISK=local

VITE_APP_NAME="${APP_NAME}"
EOF
```

Copy to `.env` for local development:

```bash
cp .env.example .env

# Generate application key
php artisan key:generate
```

## Step 4: Create a Simple Example Application

Let's create a simple blog application to demonstrate all Laravel features.

### 4.1: Create Database Migration

```bash
php artisan make:migration create_posts_table
```

Edit `database/migrations/YYYY_MM_DD_create_posts_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->boolean('published')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
```

### 4.2: Create Model

```bash
php artisan make:model Post
```

Edit `app/Models/Post.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = ['title', 'content', 'published'];

    protected $casts = [
        'published' => 'boolean',
    ];
}
```

### 4.3: Create Controller

```bash
php artisan make:controller PostController
```

Edit `app/Http/Controllers/PostController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::where('published', true)
            ->latest()
            ->get();

        return view('posts.index', compact('posts'));
    }

    public function show(Post $post)
    {
        if (!$post->published) {
            abort(404);
        }

        return view('posts.show', compact('post'));
    }
}
```

### 4.4: Create Routes

Edit `routes/web.php`:

```php
<?php

use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PostController::class, 'index']);
Route::get('/posts/{post}', [PostController::class, 'show']);
```

### 4.5: Create Views

Create `resources/views/posts/index.blade.php`:

```php
<!DOCTYPE html>
<html>
<head>
    <title>My Laravel App</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div style="max-width: 800px; margin: 0 auto; padding: 20px;">
        <h1>Laravel on DigitalOcean App Platform</h1>

        @if($posts->count() > 0)
            <div>
                @foreach($posts as $post)
                    <article style="margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid #eee;">
                        <h2>
                            <a href="/posts/{{ $post->id }}">{{ $post->title }}</a>
                        </h2>
                        <p>{{ Str::limit($post->content, 200) }}</p>
                        <small>Published {{ $post->created_at->diffForHumans() }}</small>
                    </article>
                @endforeach
            </div>
        @else
            <p>No posts yet. Check the Getting Started guide to add some!</p>
        @endif
    </div>
</body>
</html>
```

Create `resources/views/posts/show.blade.php`:

```php
<!DOCTYPE html>
<html>
<head>
    <title>{{ $post->title }} - My Laravel App</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div style="max-width: 800px; margin: 0 auto; padding: 20px;">
        <a href="/">← Back to posts</a>

        <article style="margin-top: 20px;">
            <h1>{{ $post->title }}</h1>
            <small>Published {{ $post->created_at->diffForHumans() }}</small>
            <div style="margin-top: 20px; line-height: 1.6;">
                {!! nl2br(e($post->content)) !!}
            </div>
        </article>
    </div>
</body>
</html>
```

### 4.6: Create Queued Job (Example)

```bash
php artisan make:job ProcessPost
```

Edit `app/Jobs/ProcessPost.php`:

```php
<?php

namespace App\Jobs;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Post $post)
    {
    }

    public function handle(): void
    {
        // Example: Log that we processed this post
        Log::info("Processing post: {$this->post->title}");

        // In a real app, you might:
        // - Send notifications
        // - Process images
        // - Update search index
        // etc.
    }
}
```

### 4.7: Create Scheduled Task (Example)

Edit `app/Console/Kernel.php`:

```php
<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Example: Log a message every hour
        $schedule->call(function () {
            Log::info('Scheduler is working! Time: ' . now());
        })->hourly();

        // Example: Clean up old data daily
        $schedule->command('model:prune')->daily();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
```

### 4.8: Create Database Seeder (for testing)

Edit `database/seeders/DatabaseSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Post::create([
            'title' => 'Welcome to Laravel on App Platform',
            'content' => "This is a sample blog post running on DigitalOcean App Platform.\n\nThis demonstrates Laravel with PostgreSQL, Redis, queue workers, and the scheduler all working together!",
            'published' => true,
        ]);

        Post::create([
            'title' => 'Laravel Features Working',
            'content' => "✓ Web service with Nginx + PHP-FPM\n✓ PostgreSQL database\n✓ Redis for caching and sessions\n✓ Queue workers for background jobs\n✓ Scheduler for cron tasks\n✓ Auto-scaling ready",
            'published' => true,
        ]);
    }
}
```

## Step 5: Test Locally with Docker

Build the assets first:

```bash
npm run build
```

Start the application with Docker Compose:

```bash
docker compose up -d
```

Run migrations and seed:

```bash
docker compose exec web php artisan migrate --seed
```

Test the application:

```bash
# Open in browser
open http://localhost

# Test queue worker (check logs)
docker compose logs -f queue-worker

# Test scheduler (check logs)
docker compose logs -f scheduler
```

Stop when done testing:

```bash
docker compose down
```

## Step 6: Prepare for Deployment

### 6.1: Initialize Git Repository

```bash
git init
git add .
git commit -m "Initial commit: Laravel app for App Platform"
```

### 6.2: Create GitHub Repository

```bash
# Create a new private repository on GitHub
gh repo create my-laravel-app --private --source=. --push
```

Or manually:
1. Go to GitHub and create a new repository
2. Push your code:

```bash
git remote add origin https://github.com/YOUR_USERNAME/my-laravel-app.git
git branch -M main
git push -u origin main
```

### 6.3: Update App Spec

Edit `.do/app.yaml` and update the `github:` repo references:

```yaml
github:
  repo: YOUR_USERNAME/my-laravel-app  # Update this
  branch: main
  deploy_on_push: true
```

Do this for all three components (web, queue-worker, scheduler).

## Step 7: Deploy to App Platform

### 7.1: Create the App

```bash
doctl apps create --spec .do/app.yaml
```

### 7.2: Get App ID and URL

```bash
# Get app ID
APP_ID=$(doctl apps list --format ID --no-header | head -1)

# Get app URL
doctl apps get $APP_ID --format DefaultIngress --no-header
```

### 7.3: Set Application Key

Generate and set the `APP_KEY`:

```bash
# Generate key
php artisan key:generate --show

# Set in App Platform (replace with your actual key)
doctl apps update $APP_ID --env APP_KEY="base64:YOUR_KEY_HERE"
```

Or set via the control panel:
1. Go to your app in the control panel
2. Settings → App-Level Environment Variables
3. Add `APP_KEY` with the generated value

### 7.4: Monitor Deployment

```bash
# Watch deployment
doctl apps list

# Check logs
doctl apps logs $APP_ID --type build
doctl apps logs $APP_ID --type run
```

## Step 8: Run Migrations on Production

After successful deployment:

```bash
# Option 1: Use doctl to run migration
doctl apps create-deployment $APP_ID

# Option 2: Add a PRE_DEPLOY job to .do/app.yaml
```

Or add migrations to your app.yaml:

```yaml
jobs:
  - name: migrate
    kind: PRE_DEPLOY
    github:
      repo: YOUR_USERNAME/my-laravel-app
      branch: main
    dockerfile_path: docker/web/Dockerfile
    run_command: php artisan migrate --force
    envs:
      # Same env vars as web service
```

Then redeploy:

```bash
doctl apps update $APP_ID --spec .do/app.yaml
```

## Step 9: Seed Sample Data

Create a one-time job to seed data:

```bash
# You can run this via App Platform console or add as a job
```

Or manually via app console → web component → Console tab:

```bash
php artisan db:seed
```

## Step 10: Test Your Deployed Application

Visit your app URL and verify:

- ✅ Home page loads and shows blog posts
- ✅ Individual post pages work
- ✅ Database is connected (posts are displayed)
- ✅ Assets load correctly (CSS/JS from Vite)

Check logs to verify background components:

```bash
# Queue worker logs
doctl apps logs $APP_ID --component queue-worker

# Scheduler logs
doctl apps logs $APP_ID --component scheduler
```

## Next Steps

### Add More Features

1. **User Authentication**: `php artisan breeze:install`
2. **API Endpoints**: Create RESTful APIs
3. **File Uploads**: Configure DigitalOcean Spaces
4. **More Jobs**: Add email notifications, image processing
5. **More Scheduled Tasks**: Reports, cleanup, backups

### Configure Spaces for File Uploads

```bash
# Create Spaces bucket
doctl spaces create my-laravel-app-storage --region nyc3

# Create access keys
doctl spaces access create

# Update environment variables
# AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_BUCKET, etc.
```

### Set Up Custom Domain

```bash
doctl apps update $APP_ID --domain your-domain.com
```

### Monitor Your App

- **App Platform Dashboard**: CPU, memory, requests
- **Database Dashboard**: Query performance, connections
- **Log Forwarding**: Send to Datadog, Papertrail, etc.

### Scale Your App

Auto-scaling is already configured! Your app will scale from:
- **Web**: 2-10 instances based on CPU
- **Queue Worker**: 1-5 instances based on CPU

To adjust:

```yaml
autoscaling:
  min_instance_count: 3  # Increase minimum
  max_instance_count: 20  # Increase maximum
```

## Troubleshooting

### Build Failures

```bash
# Check build logs
doctl apps logs $APP_ID --type build

# Common issues:
# - Missing APP_KEY (set in environment variables)
# - npm build failures (check package.json)
# - Composer dependency issues
```

### Database Connection Issues

```bash
# Check if database is running
doctl databases list

# Verify connection strings are correct
doctl apps get $APP_ID --format Spec
```

### Queue Jobs Not Processing

```bash
# Check queue worker logs
doctl apps logs $APP_ID --component queue-worker

# Dispatch a test job to verify
# (via tinker or create a test endpoint)
```

## Summary

You now have a complete Laravel application running on DigitalOcean App Platform with:

✅ Web service (Nginx + PHP-FPM)
✅ PostgreSQL database
✅ Redis cache and queue
✅ Queue worker for background jobs
✅ Scheduler for cron tasks
✅ Auto-scaling
✅ Production-ready configuration

**Estimated cost**: $152-445/month (scales with traffic)

For a lower-cost option, see the Starter Mode configuration in `.do/examples/`.

## Resources

- [Laravel Documentation](https://laravel.com/docs)
- [App Platform Documentation](https://docs.digitalocean.com/products/app-platform/)
- [Template README](./README.md)
- [Deployment Guide](./DEPLOYMENT_GUIDE.md)
- [Template Usage Guide](./TEMPLATE_USAGE.md)

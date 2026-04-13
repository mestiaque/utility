<?php

namespace ME\Utility\Http\Controllers;

use ZipArchive;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use ME\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class GeneratePackageController extends Controller
{

    public function index()
    {
        return view('utility::packages.index');
    }

    public function generate(Request $request)
    {
        $name = $request->name ?? 'mypackage';
        // Sanitize and validate input
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
        if (!$name) abort(400, 'Invalid package name.');

        $nameStudly = Str::studly($name);   // For namespace & classes
        $nameLower  = Str::snake($name);    // For folders, routes, config
        $nameStudlyLower = strtolower($nameStudly); // For README
        $tableName  = Str::plural($nameLower);
        $basePath   = storage_path("app/temp_package/{$nameLower}");

        $this->cleanOldFolder($basePath);
        $this->createFolders($basePath);

        // Dynamic Composer & ServiceProvider
        $this->makeComposerJson($nameLower, $nameStudly, $basePath);
        $this->makeServiceProvider($nameStudly, $nameLower, $basePath);

        $this->makeController($nameStudly, $basePath);
        $this->makeModel($nameStudly, $tableName, $basePath);
        $this->makeMigration($tableName, $basePath);
        $this->makeRoutes($nameStudly, $nameLower, $basePath);
        $this->makeLang($nameStudly, $basePath);
        $this->makeConfig($nameLower, $basePath);
        $this->makeReadme($nameStudly, $nameStudlyLower, $basePath);

        return $this->zipAndDownload($nameLower, $basePath);
    }

    private function cleanOldFolder($basePath)
    {
        if (File::exists($basePath)) File::deleteDirectory($basePath);
    }

    private function createFolders($basePath)
    {
        $folders = [
            "src/Config",
            "src/Http/Controllers",
            "src/Models",
            "src/database/migrations",
            "src/resources/lang/en",
            "src/resources/views",
            "src/routes",
        ];
        foreach ($folders as $folder) File::makeDirectory($basePath.'/'.$folder, 0755, true);
    }

    private function makeComposerJson($nameLower, $nameStudly, $basePath)
    {
        $namespace = "ME\\" . $nameStudly . "\\";
        $providerClass = "{$nameStudly}ServiceProvider";

        $composerArray = [
            "name" => "mestiaque/{$nameLower}",
            "description" => "{$nameStudly} module for Laravel (by M. Estiaque Ahmed Khan)",
            "type" => "library",
            "keywords" => ["Laravel", $nameStudly, "Estiaque"],
            "authors" => [
                [
                    "name" => "M. Estiaque Ahmed Khan",
                    "email" => "info@mestiaque.com",
                    "homepage" => "https://mestiaque.com"
                ]
            ],
            "autoload" => [
                "psr-4" => [
                    $namespace => "src/"
                ]
            ],
            "extra" => [
                "laravel" => [
                    "providers" => [
                        "{$namespace}{$providerClass}"
                    ]
                ]
            ],
            "license" => "MIT",
            "minimum-stability" => "dev",
            "prefer-stable" => true
        ];

        File::put($basePath.'/composer.json', json_encode($composerArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function makeServiceProvider($nameStudly, $nameLower, $basePath)
    {
        $namespace = "ME\\" . $nameStudly;
        $providerClass = "{$nameStudly}ServiceProvider";

        $content = "<?php
namespace {$namespace};

use Illuminate\Support\ServiceProvider;

class {$providerClass} extends ServiceProvider
{
    public function boot()
    {
        \$this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        \$this->loadRoutesFrom(__DIR__ . '/routes/api.php');
        \$this->loadViewsFrom(__DIR__ . '/resources/views', '{$nameLower}');
        \$this->loadTranslationsFrom(__DIR__.'/resources/lang', '{$nameLower}');
        \$this->loadMigrationsFrom(__DIR__.'/database/migrations');
        \$this->publishes([
            __DIR__.'/Config' => config_path('{$nameLower}'),
        ], '{$nameLower}-config');
    }

    public function register()
    {
        if (file_exists(__DIR__ . '/Config/config.php')) {
            \$this->mergeConfigFrom(__DIR__ . '/Config/config.php', '{$nameLower}');
        }

        if (file_exists(__DIR__ . '/Config/sidebar.php')) {
            \$this->mergeConfigFrom(__DIR__ . '/Config/sidebar.php', 'sidebar');
        }

        if (file_exists(__DIR__ . '/Config/permissions.php')) {
            \$this->mergeConfigFrom(__DIR__ . '/Config/permissions.php', 'permissions');
        }
    }
}";
        File::put($basePath."/src/{$providerClass}.php", $content);
    }

    private function makeController($nameStudly, $basePath)
    {
        $content = "<?php
namespace ME\\{$nameStudly}\\Http\\Controllers;

use ME\\Http\\Controllers\\Controller;

class {$nameStudly}Controller extends Controller
{
    public function index()
    {
        return '{$nameStudly} works!';
    }
}";
        File::put($basePath."/src/Http/Controllers/{$nameStudly}Controller.php", $content);
    }

    private function makeModel($nameStudly, $tableName, $basePath)
    {
        $content = "<?php
namespace ME\\{$nameStudly}\\Models;

use Illuminate\\Database\\Eloquent\\Model;

class {$nameStudly} extends Model
{
    protected \$fillable = [];
    protected \$table = '{$tableName}';
}";
        File::put($basePath."/src/Models/{$nameStudly}.php", $content);
    }

    private function makeMigration($tableName, $basePath)
    {
        $migrationName = date('Y_m_d_His')."_create_{$tableName}_table.php";
        $content = "<?php
use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name')->nullable();
            \$table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('{$tableName}');
    }
};";
        File::put($basePath."/src/database/migrations/{$migrationName}", $content);
    }

    private function makeRoutes($nameStudly, $nameLower, $basePath)
    {
        $web = "<?php
use Illuminate\\Support\\Facades\\Route;
use ME\\{$nameStudly}\\Http\\Controllers\\{$nameStudly}Controller;

Route::get('/{$nameLower}', [{$nameStudly}Controller::class, 'index']);";

        $api = "<?php
use Illuminate\\Support\\Facades\\Route;
use ME\\{$nameStudly}\\Http\\Controllers\\{$nameStudly}Controller;

Route::prefix('api/{$nameLower}')->group(function() {
    Route::get('/', [{$nameStudly}Controller::class, 'index']);
});";

        File::put($basePath."/src/routes/web.php", $web);
        File::put($basePath."/src/routes/api.php", $api);
    }

    private function makeLang($nameStudly, $basePath)
    {
        $content = "<?php
return [
    'welcome' => '{$nameStudly} package loaded successfully',
];";
        File::put($basePath."/src/resources/lang/en/message.php", $content);
    }

    private function makeConfig($nameLower, $basePath)
    {
        $content = "<?php
return [
    'key' => 'value',
];";
        File::put($basePath."/src/Config/config.php", $content);
        $content = "<?php
return [
    'key' => 'value',
];";
        File::put($basePath."/src/Config/permission.php", $content);
        $content = "<?php
return [
    'key' => 'value',
];";
        File::put($basePath."/src/Config/sidebar.php", $content);
    }

    private function makeReadme($nameStudly, $nameStudlyLower, $basePath)
    {
        $content = "# {$nameStudly} Package

Auto-generated Laravel package by ME Utility.

## Installation

```bash
composer require mestiaque/{$nameStudlyLower}
```

## Usage

After installing the package, the service provider will be auto-discovered by Laravel.

### Routes

- Web: `/{$nameStudlyLower}`
- API: `/api/{$nameStudlyLower}`

### Views

```php
view('{$nameStudlyLower}::index');
```

### Translations

```php
trans('{$nameStudlyLower}::message.welcome');
```

### Config

Publish the config file:

```bash
php artisan vendor:publish --tag={$nameStudlyLower}-config
```
";
        File::put($basePath."/README.md", $content);
    }

    private function zipAndDownload($nameLower, $basePath)
    {
        $zipFile = storage_path("app/{$nameLower}.zip");
        if (File::exists($zipFile)) File::delete($zipFile);

        $zip = new ZipArchive;
        if ($zip->open($zipFile, ZipArchive::CREATE) !== TRUE) {
            abort(500, 'Failed to create ZIP file.');
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($basePath) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $closed = $zip->close();
        if (!$closed) {
            abort(500, 'Failed to close ZIP file properly.');
        }

        // Clean up temp folder before download
        $this->cleanOldFolder($basePath);

        return response()->download($zipFile, "{$nameLower}.zip")->deleteFileAfterSend(true);
    }

}

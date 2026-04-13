<?php
namespace ME\Utility\Http\Controllers;

use ZipArchive;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Illuminate\Support\Facades\DB;
use ME\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class UtilityController extends Controller
{
    public function index()
    {
        return view('utility::index');
    }

    public function weddingCard()
    {
        return view('utility::wedding-card');
    }

    public function laravelFeatureGenerate()
    {
        return view('utility::feature.index');
    }

    public function generateLaravelFeature(Request $request)
    {
        DB::beginTransaction();
        try {
            // 1. Validation & Variables
            $name = Str::studly($request->name);
            $lower = Str::snake($name);
            $tableName = Str::plural($lower);
            $columns = $request->columns;
            $btnBase = $request->btn_base ?? 'btn-encodex';
            $tableClass = $request->table_class ?? 'table-encodex';
            $rPref = $request->route_prefix ?? 'me';
            $fullRoute = "{$rPref}.{$lower}";

            $basePath = storage_path("app/temp_gen/{$lower}");
            if (File::exists($basePath)) File::deleteDirectory($basePath);
            File::makeDirectory($basePath, 0755, true);

            // 2. SQL Schema Generation
            $sqlCols = ["`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY"];
            foreach ($columns as $c) {
                $laravelType = strtolower($c['type']);
                $length = !empty($c['length']) ? $c['length'] : null;

                // Map Laravel types to SQL types
                $sqlType = match ($laravelType) {
                    'string'        => "VARCHAR(" . ($length ?? 255) . ")",
                    'text'          => "TEXT",
                    'mediumtext'    => "MEDIUMTEXT",
                    'longtext'      => "LONGTEXT",
                    'integer'       => "INT",
                    'biginteger'    => "BIGINT",
                    'tinyinteger'   => "TINYINT",
                    'smallinteger'  => "SMALLINT",
                    'boolean'       => "TINYINT(1)",
                    'decimal'       => "DECIMAL(" . ($length ?? "10,2") . ")",
                    'float'         => "FLOAT",
                    'double'        => "DOUBLE",
                    'date'          => "DATE",
                    'datetime'      => "DATETIME",
                    'timestamp'     => "TIMESTAMP",
                    'time'          => "TIME",
                    'json'          => "JSON",
                    'char'          => "CHAR(" . ($length ?? 255) . ")",
                    default         => strtoupper($laravelType),
                };

                $null = $c['is_null'] ? "NULL" : "NOT NULL";

                // Handle Default Values (Special case for NULL or empty)
                $def = "";
                if (isset($c['default']) && $c['default'] !== '') {
                    if (strtoupper($c['default']) === 'NULL') {
                        $def = "DEFAULT NULL";
                    } elseif (is_numeric($c['default'])) {
                        $def = "DEFAULT {$c['default']}";
                    } else {
                        $def = "DEFAULT '{$c['default']}'";
                    }
                }

                $sqlCols[] = "`{$c['name']}` $sqlType $null $def";
            }
            // Add Tracking Columns
            $sqlCols[] = "`created_by` BIGINT UNSIGNED NULL";
            $sqlCols[] = "`updated_by` BIGINT UNSIGNED NULL";
            $sqlCols[] = "`created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP";
            $sqlCols[] = "`updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
            $sqlCols[] = "`deleted_at` TIMESTAMP NULL";
            $sqlCols[] = "`deleted_by` BIGINT UNSIGNED NULL";

            // Then join them
            $sqlContent = "CREATE TABLE `{$tableName}` (\n  " . implode(",\n  ", $sqlCols) . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

            File::put("$basePath/schema.sql", $sqlContent);
            // --- 2.1. Laravel Migration Generation ---
            $migrationFields = "";
            foreach ($columns as $c) {
                $laravelType = strtolower($c['type']);
                $length = !empty($c['length']) ? $c['length'] : null;

                // Build the method call: e.g., $table->string('title', 255)
                if ($laravelType === 'string' || $laravelType === 'char') {
                    $fieldLine = "\$table->{$laravelType}('{$c['name']}', " . ($length ?? 255) . ")";
                } elseif ($laravelType === 'decimal') {
                    $fieldLine = "\$table->decimal('{$c['name']}', " . ($length ?? "10, 2") . ")";
                } else {
                    $fieldLine = "\$table->{$laravelType}('{$c['name']}')";
                }

                // Add Modifiers
                if ($c['is_null']) $fieldLine .= "->nullable()";

                if (isset($c['default']) && $c['default'] !== '') {
                    if (is_numeric($c['default'])) {
                        $fieldLine .= "->default({$c['default']})";
                    } else {
                        $fieldLine .= "->default('{$c['default']}')";
                    }
                }

                $migrationFields .= "            {$fieldLine};\n";
            }

            $migName = date('Y_m_d_His') . "_create_{$tableName}_table.php";
$migrationContent = "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
{$migrationFields}            \$table->unsignedBigInteger('created_by')->nullable();
            \$table->unsignedBigInteger('updated_by')->nullable();
            \$table->timestamps();
            \$table->softDeletes();
            \$table->unsignedBigInteger('deleted_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};";

File::put("$basePath/{$migName}", $migrationContent);

            // 3. Model Generation
            $fillables = implode("', '", array_column($columns, 'name'));
            File::put("$basePath/{$name}.php", "<?php\n\nnamespace App\Models;\n\nuse Illuminate\Database\Eloquent\Model;\n\nclass {$name} extends Model {\n    protected \$fillable = ['$fillables'];\n}");

            // 4. Controller Generation (With Middleware & Transactions)
            File::put("$basePath/{$name}Controller.php", $this->getControllerTemplate($name, $lower, $fullRoute));

            // 5. Blade Views (Index, Create, Edit, Show)
            $this->generateBlades($basePath, $name, $lower, $columns, $fullRoute, $btnBase, $tableClass);

            // 6. Routes File
            File::put("$basePath/routes.php", "Route::resource('{{$lower}}', {$name}Controller::class);");


            // 7. Final Step: Create Flat ZIP
            $zipPath = storage_path("app/{$lower}.zip");
            $zip = new \ZipArchive;
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                $files = File::allFiles($basePath);
                foreach ($files as $file) {
                    $zip->addFile($file->getRealPath(), $file->getFilename());
                }
                $zip->close();
            }

            DB::commit();
            File::deleteDirectory($basePath);
            return response()->download($zipPath)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

private function getControllerTemplate($name, $lower, $fullRoute) {
    return "<?php

namespace App\Http\Controllers;

use App\Models\\$name;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class {$name}Controller extends Controller
{
    public function __construct()
    {
        \$this->middleware('authorization:{$lower}.view')->only(['index', 'view']);
        \$this->middleware('authorization:{$lower}.create')->only(['create', 'store']);
        \$this->middleware('authorization:{$lower}.edit')->only(['edit', 'update']);
        \$this->middleware('authorization:{$lower}.delete')->only('delete');
    }

    public function index()
    {
        return view('{$lower}.index', ['data' => $name::all()]);
    }

    public function create()
    {
        return view('{$lower}.create');
    }

    public function store(Request \$r)
    {
        DB::beginTransaction();
        try {
            $name::create(\$r->all());
            DB::commit();
            return redirect()->route('{$fullRoute}.index')->with('success', '{$name} created successfully');
        } catch (\Exception \$e) {
            DB::rollBack();
            Log::error(\$e->getMessage());
            return back()->withErrors('Failed to create {$name}')->withInput();
        }
    }

    public function edit(\$id)
    {
        return view('{$lower}.edit', ['item' => $name::findOrFail(\$id)]);
    }

    public function update(Request \$r, \$id)
    {
        DB::beginTransaction();
        try {
            $name::findOrFail(\$id)->update(\$r->all());
            DB::commit();
            return redirect()->route('{$fullRoute}.index')->with('success', '{$name} updated successfully');
        } catch (\Exception \$e) {
            DB::rollBack();
            Log::error(\$e->getMessage());
            return back()->withErrors('Failed to update {$name}');
        }
    }

    public function view(\$id)
    {
        return view('{$lower}.view', ['item' => $name::findOrFail(\$id)]);
    }

    public function delete(\$id)
    {
        try {
            $name::destroy(\$id);
            return redirect()->route('{$fullRoute}.index')->with('success', '{$name} deleted successfully');
        } catch (\Exception \$e) {
            Log::error(\$e->getMessage());
            return back()->withErrors('Delete failed');
        }
    }
}";
}


    private function generateBlades($basePath, $name, $lower, $columns, $fullRoute, $btnBase, $tableClass) {
        $pages = ['index', 'create', 'edit', 'show'];
        foreach ($pages as $p) {
            $isIndex = ($p === 'index');
            $html = "@extends('me::master')\n@section('title', '".ucfirst($p)." $name')\n\n@push('buttons')\n";
            $html .= $isIndex ? "    <a href=\"{{ route('$fullRoute.create') }}\" class=\"btn btn-sm {$btnBase}-create\"><i class=\"fas fa-plus\"></i> @lang('Create')</a>\n"
                             : "    <a href=\"{{ route('$fullRoute.index') }}\" class=\"btn btn-sm {$btnBase}-list\"><i class=\"fas fa-list\"></i> @lang('List')</a>\n";
            $html .= "@endpush\n\n@section('content')\n";

            if ($isIndex) {
                $html .= "<table class=\"table table-sm table-bordered {$tableClass}\">\n    <thead><tr>";
                foreach ($columns as $c) $html .= "<th>".ucfirst($c['name'])."</th>";
                $html .= "<th>Actions</th></tr></thead>\n    <tbody>\n    @foreach(\$data as \$row)\n    <tr>";
                foreach ($columns as $c) $html .= "<td>{{ \$row->{$c['name']} }}</td>";
                $html .= "\n        <td class=\"text-center\">\n            <div class=\"d-inline-flex gap-1\">\n";
                $html .= "                <a href=\"{{ route('$fullRoute.show', \$row->id) }}\" class=\"btn btn-sm {$btnBase}-show\"><i class=\"fas fa-eye\"></i></a>\n";
                $html .= "                <a href=\"{{ route('$fullRoute.edit', \$row->id) }}\" class=\"btn btn-sm {$btnBase}-edit\"><i class=\"fas fa-edit\"></i></a>\n";
                $html .= "                <form action=\"{{ route('$fullRoute.destroy', \$row->id) }}\" method=\"POST\">@csrf @method('DELETE')\n";
                $html .= "                    <button type=\"submit\" class=\"btn btn-sm {$btnBase}-delete\" onclick=\"return confirm('Delete?')\"><i class=\"fas fa-trash\"></i></button>\n";
                $html .= "                </form>\n            </div>\n        </td>\n    </tr>\n    @endforeach\n    </tbody>\n</table>\n";
            } else {
                $btnText = ($p === 'edit') ? "Update" : "Save";
                $html .= "<form action=\"{{ route('$fullRoute.store') }}\" method=\"POST\">\n    @csrf\n    <div class=\"mt-4 text-end\">\n        <button type=\"submit\" class=\"btn {$btnBase} float-right\"><i class=\"fas fa-save me-1\"></i> @lang('$btnText $name')</button>\n    </div>\n</form>\n";
            }
            $html .= "@endsection";
            File::put("$basePath/{$p}.blade.php", $html);
        }
    }

    public function feelMusic()
    {
        return view('utility::feel-music');
    }




}

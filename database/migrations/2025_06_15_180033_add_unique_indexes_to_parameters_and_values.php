<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('parameters', function (Blueprint $table) {
            $table->unique('slug');
        });

        Schema::table('parameter_values', function (Blueprint $table) {
            $table->unique(['parameter_id', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parameters', function (Blueprint $table) {
            $table->dropUnique(['parameters_slug_unique']);
        });

        Schema::table('parameter_values', function (Blueprint $table) {
            $table->dropUnique(['parameter_values_parameter_id_value_unique']);
        });
    }
};

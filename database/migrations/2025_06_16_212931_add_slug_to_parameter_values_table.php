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
        Schema::table('parameter_values', function (Blueprint $table) {
            $table->string('slug')->after('parameter_id');

            $table->unique(['parameter_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parameter_values', function (Blueprint $table) {
            $table->dropUnique(['parameter_id', 'slug']);
            $table->dropColumn('slug');
        });
    }
};

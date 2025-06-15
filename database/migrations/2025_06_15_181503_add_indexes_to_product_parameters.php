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
        Schema::table('product_parameters', function (Blueprint $table) {
            $table->index('product_id');
            $table->index('parameter_value_id');
            $table->unique(['product_id', 'parameter_value_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_parameters', function (Blueprint $table) {
            $table->dropUnique(['product_parameters_product_id_parameter_value_id_unique']);
            $table->dropIndex(['product_id']);
            $table->dropIndex(['parameter_value_id']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perfis', function (Blueprint $table) {
            $table->string('esfera', 20)->default('federal')->after('descricao');
            $table->string('status', 10)->default('ativo')->after('esfera');
        });
    }

    public function down(): void
    {
        Schema::table('perfis', function (Blueprint $table) {
            $table->dropColumn(['esfera', 'status']);
        });
    }
};

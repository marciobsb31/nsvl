<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perfil_usuario', function (Blueprint $table) {
            $table->string('uf', 2)->nullable()->after('data_fim_vigencia');
            $table->string('municipio', 100)->nullable()->after('uf');
            $table->string('orgao', 255)->nullable()->after('municipio');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('perfil_usuario_ativo_id')
                ->nullable()
                ->after('municipio_lotacao')
                ->constrained('perfil_usuario')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('perfil_usuario_ativo_id');
        });

        Schema::table('perfil_usuario', function (Blueprint $table) {
            $table->dropColumn(['uf', 'municipio', 'orgao']);
        });
    }
};

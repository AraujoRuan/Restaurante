<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // Para MySQL/MariaDB, ajusta o ENUM explicitamente
        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','gerente','garcom','caixa','cozinha','cliente') NOT NULL DEFAULT 'cliente'");
        }

        // Para outros bancos (sqlite, pg etc.), o enum é mapeado para string,
        // então normalmente não é necessário alterar o schema aqui.
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','gerente','garcom','caixa','cozinha') NOT NULL DEFAULT 'garcom'");
        }
    }
};

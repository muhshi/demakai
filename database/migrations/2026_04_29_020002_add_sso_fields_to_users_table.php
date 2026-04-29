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
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
            
            $table->string('sipetra_id')->nullable()->unique()->after('email');
            $table->text('sipetra_token')->nullable()->after('sipetra_id');
            $table->text('sipetra_refresh_token')->nullable()->after('sipetra_token');
            $table->string('nip')->nullable()->after('sipetra_refresh_token');
            $table->string('jabatan')->nullable()->after('nip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();
            
            $table->dropColumn([
                'sipetra_id',
                'sipetra_token',
                'sipetra_refresh_token',
                'nip',
                'jabatan',
            ]);
        });
    }
};

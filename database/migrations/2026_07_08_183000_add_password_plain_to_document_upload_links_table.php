<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_upload_links') && !Schema::hasColumn('document_upload_links', 'password_plain')) {
            Schema::table('document_upload_links', function (Blueprint $table) {
                $table->string('password_plain')->nullable()->after('password');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('document_upload_links') && Schema::hasColumn('document_upload_links', 'password_plain')) {
            Schema::table('document_upload_links', function (Blueprint $table) {
                $table->dropColumn('password_plain');
            });
        }
    }
};

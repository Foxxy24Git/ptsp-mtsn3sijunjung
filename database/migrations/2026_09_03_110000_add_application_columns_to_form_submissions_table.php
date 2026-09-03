<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->string('receipt_code', 20)->nullable()->unique();
            $table->string('applicant_name', 150)->nullable();
            $table->string('applicant_whatsapp', 20)->nullable()->index();
            $table->string('applicant_email', 150)->nullable();
            $table->string('status', 20)->default('diajukan')->index();
            $table->text('admin_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'receipt_code', 'applicant_name', 'applicant_whatsapp',
                'applicant_email', 'status', 'admin_note',
            ]);
        });
    }
};

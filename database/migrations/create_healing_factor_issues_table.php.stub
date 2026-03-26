<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('healing_factor_issues', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint', 64)->index();
            $table->string('source')->default('nightwatch');
            $table->string('organization_id')->nullable();
            $table->string('application_id')->nullable();
            $table->string('environment_id')->nullable();
            $table->text('title');
            $table->string('exception_class')->nullable();
            $table->text('exception_message')->nullable();
            $table->text('stacktrace')->nullable();
            $table->string('status')->default('pending');
            $table->string('category')->nullable();
            $table->string('cli_tool')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('pr_url')->nullable();
            $table->text('failure_reason')->nullable();
            $table->text('cli_output')->nullable();
            $table->text('cli_error_output')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['fingerprint', 'status'], 'healing_factor_issues_fingerprint_status_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('healing_factor_issues');
    }
};

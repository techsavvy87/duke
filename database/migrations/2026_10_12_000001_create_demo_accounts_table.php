<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables whose rows belong to one environment: NULL = the real facility,
     * otherwise the demo account that created them.
     */
    private array $environmentTables = [
        'users',
        'pet_profiles',
        'appointments',
        'invoices',
        'notifications',
        'kennels',
        'rooms',
        'customer_complaints',
        'employee_attendance',
        'incident_reports',
        'maintenance_issues',
        'appointment_audit_logs',
        'customer_packages',
        'customer_invites',
        'inventory_categories',
        'inventory_items',
    ];

    /**
     * Environment tables whose create migrations already define demo_account_id.
     */
    private array $environmentTablesWithColumn = [
        'services',
        'time_slots',
        'transactions',
        'payment_links',
        'payouts',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('demo_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('facility_name');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('demo_account_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demo_account_id')->constrained('demo_accounts')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['demo_account_id', 'service_id']);
        });

        if (!Schema::hasColumn('users', 'is_demo')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_demo')->default(false)->after('status');
            });
        }

        foreach ($this->environmentTables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'demo_account_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('demo_account_id')->nullable()->constrained('demo_accounts');
                });
            }
        }

        // These tables define demo_account_id in their create migrations; databases that ran
        // those before the column existed get it here. The foreign key needs demo_accounts,
        // so it is always added here.
        foreach ($this->environmentTablesWithColumn as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'demo_account_id')) {
                    $table->foreignId('demo_account_id')->nullable();
                }
                $table->foreign('demo_account_id')->references('id')->on('demo_accounts');
            });
        }

        if (!Schema::hasColumn('services', 'source_service_id')) {
            Schema::table('services', function (Blueprint $table) {
                $table->foreignId('source_service_id')->nullable()->constrained('services')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->environmentTablesWithColumn as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['demo_account_id']);
            });
        }

        foreach ($this->environmentTables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'demo_account_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('demo_account_id');
                });
            }
        }

        if (Schema::hasColumn('users', 'is_demo')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_demo');
            });
        }

        Schema::dropIfExists('demo_account_service');
        Schema::dropIfExists('demo_accounts');
    }
};

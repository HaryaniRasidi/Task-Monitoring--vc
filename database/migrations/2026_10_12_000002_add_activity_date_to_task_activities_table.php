<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_activities', function (Blueprint $table) {
            $table->date('activity_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('task_activities', function (Blueprint $table) {
            $table->dropColumn('activity_date');
        });
    }
};

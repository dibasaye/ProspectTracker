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
        Schema::table('contracts', function (Blueprint $table) {
            $table->boolean('is_editable')->default(true)->after('content');
            $table->unsignedBigInteger('validated_by')->nullable()->after('is_editable');
            $table->timestamp('validated_at')->nullable()->after('validated_by');
            
            $table->foreign('validated_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropColumn(['is_editable', 'validated_by', 'validated_at']);
        });
    }
};

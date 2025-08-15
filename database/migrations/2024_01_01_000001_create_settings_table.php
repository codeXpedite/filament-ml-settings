<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('group')->default('general');
            $table->string('tab')->nullable();
            $table->string('type')->default('text');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('options')->nullable();
            $table->json('rules')->nullable();
            $table->text('default_value')->nullable();
            $table->text('value')->nullable();
            $table->boolean('is_translatable')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->index(['group', 'tab']);
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
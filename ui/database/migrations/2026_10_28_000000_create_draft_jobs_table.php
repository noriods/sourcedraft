<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('draft_jobs', function (Blueprint $t) {
            $t->id();
            $t->string('project');
            $t->string('recipe');
            $t->text('topic');
            $t->string('status')->default('queued'); // queued, running, done, failed
            $t->string('run')->nullable();
            $t->text('error')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('draft_jobs');
    }
};

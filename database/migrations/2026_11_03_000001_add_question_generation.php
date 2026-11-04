<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('content');
            $table->unsignedBigInteger('wiki_page_id')->nullable()->unique();
            $table->string('source_name')->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->string('license')->nullable();
            $table->timestamps();
        });
        Schema::table('quiz_sets', function (Blueprint $table) {
            $table->foreignId('article_id')->nullable()->index();
            $table->string('source')->default('manual');
        });
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('quiz_sets', fn (Blueprint $table) => $table->foreign('article_id')->references('id')->on('articles')->restrictOnDelete());
        }
        // Add relations to existing sets without changing questions, scores or publication.
        DB::table('quiz_sets')->orderBy('id')->chunkById(100, function ($sets) {
            foreach ($sets as $set) {
                $id = DB::table('articles')->insertGetId(['title' => $set->title, 'content' => $set->reading_passage, 'source_url' => $set->source_url, 'created_at' => now(), 'updated_at' => now()]);
                DB::table('quiz_sets')->where('id', $set->id)->update(['article_id' => $id]);
            }
        });
        Schema::create('ai_generation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('article_id')->constrained()->restrictOnDelete();
            $table->foreignId('quiz_set_id')->nullable()->constrained()->nullOnDelete();
            $table->string('model');
            $table->string('status');
            $table->text('error_message')->nullable();
            $table->longText('response_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generation_logs');
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('quiz_sets', fn (Blueprint $table) => $table->dropForeign(['article_id']));
        }
        Schema::table('quiz_sets', function (Blueprint $table) {
            $table->dropIndex(['article_id']);
            $table->dropColumn(['article_id', 'source']);
        });
        Schema::dropIfExists('articles');
    }
};

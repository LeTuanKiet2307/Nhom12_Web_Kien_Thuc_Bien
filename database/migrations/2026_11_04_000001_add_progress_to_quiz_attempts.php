<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dateTime('reading_started_at', 3)->nullable()->after('duration_seconds');
            $table->dateTime('answering_started_at', 3)->nullable()->after('reading_started_at');
            $table->foreignId('current_question_id')->nullable()->after('answering_started_at')
                ->constrained('questions')->nullOnDelete();
            $table->dateTime('question_started_at', 3)->nullable()->after('current_question_id');
            $table->timestamp('completed_at')->nullable()->after('question_started_at');
            $table->index(['user_id', 'completed_at']);
        });

        // Lịch sử cũ: mọi lượt làm bài trước đây đều đã hoàn thành
        DB::table('quiz_attempts')->whereNull('completed_at')->update([
            'completed_at' => DB::raw('created_at'),
            'answering_started_at' => DB::raw('created_at'),
        ]);

        // Gỡ dòng trùng do lỗi cũ (giữ id nhỏ nhất) rồi mới thêm khóa duy nhất.
        // Bọc subquery trong bảng dẫn xuất, nếu không MySQL báo lỗi 1093.
        DB::statement('
            DELETE FROM quiz_attempt_answers
            WHERE id NOT IN (
                SELECT id FROM (
                    SELECT MIN(id) AS id FROM quiz_attempt_answers
                    GROUP BY quiz_attempt_id, question_id
                ) AS keep_rows
            )
        ');

        Schema::table('quiz_attempt_answers', function (Blueprint $table) {
            $table->unique(['quiz_attempt_id', 'question_id']);
        });
    }

    // MariaDB có thể dùng index mới để đỡ index khóa ngoại đã có,
    // nên tạm gỡ khóa ngoại rồi thêm lại sau khi bỏ index/khóa duy nhất.
    public function down(): void
    {
        Schema::table('quiz_attempt_answers', function (Blueprint $table) {
            $table->dropForeign(['quiz_attempt_id']);
            $table->dropUnique(['quiz_attempt_id', 'question_id']);
        });

        Schema::table('quiz_attempt_answers', function (Blueprint $table) {
            $table->foreign('quiz_attempt_id')->references('id')->on('quiz_attempts')->cascadeOnDelete();
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['current_question_id']);
            $table->dropIndex(['user_id', 'completed_at']);
            $table->dropColumn([
                'reading_started_at',
                'answering_started_at',
                'current_question_id',
                'question_started_at',
                'completed_at',
            ]);
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};

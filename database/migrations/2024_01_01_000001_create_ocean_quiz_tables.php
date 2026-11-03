<?php
// [Thành viên 1 - Owner] database/migrations/2024_01_01_000001_create_ocean_quiz_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Bảng Users (Hỗ trợ phân quyền User & Admin + Điểm tích lũy)
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->enum('role', ['user', 'admin'])->default('user');
                $table->unsignedBigInteger('total_score')->default(0);
                $table->unsignedInteger('quizzes_completed')->default(0);
                $table->rememberToken();
                $table->timestamps();
            });
        } else {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'role')) {
                    $table->enum('role', ['user', 'admin'])->default('user')->after('password');
                }
                if (!Schema::hasColumn('users', 'total_score')) {
                    $table->unsignedBigInteger('total_score')->default(0)->after('role');
                }
                if (!Schema::hasColumn('users', 'quizzes_completed')) {
                    $table->unsignedInteger('quizzes_completed')->default(0)->after('total_score');
                }
            });
        }

        // 2. Bảng Quiz Sets (Chứa chủ đề, độ sâu đại dương và bài đọc kiến thức)
        Schema::create('quiz_sets', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('topic');
            $table->string('ocean_zone')->default('Sunlit Zone (0m - 200m)');
            $table->longText('reading_passage');
            $table->string('source_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 3. Bảng Questions (4 đáp án lưu dạng JSON, vị trí đáp án đúng 0-3, giải thích)
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_set_id')->constrained('quiz_sets')->cascadeOnDelete();
            $table->text('question_text');
            $table->json('options'); // Mảng 4 phần tử ["Đáp án A", "Đáp án B", "Đáp án C", "Đáp án D"]
            $table->unsignedTinyInteger('correct_option_index'); // 0, 1, 2, hoặc 3
            $table->text('explanation')->nullable();
            $table->unsignedInteger('time_limit')->default(20); // Giây cho mỗi câu Kahoot
            $table->timestamps();
        });

        // 4. Bảng Quiz Attempts (Lịch sử mỗi lần làm bài của User)
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('quiz_set_id')->constrained('quiz_sets')->cascadeOnDelete();
            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('total_questions')->default(0);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->timestamps();
        });

        // 5. Bảng Quiz Attempt Answers (Chi tiết từng câu trả lời để làm tính năng Review Mistakes)
        Schema::create('quiz_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->tinyInteger('selected_option_index')->nullable(); // -1 hoặc null nếu hết giờ
            $table->boolean('is_correct')->default(false);
            $table->float('time_taken')->default(0); // Thời gian phản xạ (giây)
            $table->unsignedInteger('points_earned')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempt_answers');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('quiz_sets');
    }
};
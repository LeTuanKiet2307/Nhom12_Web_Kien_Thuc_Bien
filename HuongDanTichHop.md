# Hướng dẫn tích hợp lại database — nhánh FixQuizLogic

Tài liệu này dùng khi đưa nhánh `FixQuizLogic` vào một database đang có dữ liệu: sao lưu, cập nhật cột, chạy thử, kiểm tra và khôi phục nếu lỗi.

Ngăn xếp: Laravel 12, PHP ≥ 8.2, MySQL hoặc MariaDB (cần `pdo_mysql`). Không dùng JavaScript.

## 0. Tóm tắt

| Bước | Việc | Lệnh chính |
|---|---|---|
| 1 | Sao lưu database | `mysqldump ... > file.sql` |
| 2 | Ghi lại số liệu trước khi đổi | các câu `SELECT COUNT(*)` |
| 3 | Lấy code nhánh, cài thư viện | `git switch`, `composer install` |
| 4 | Xem trước rồi chạy migration | `php artisan migrate --pretend`, `php artisan migrate` |
| 5 | Kiểm tra cột và số liệu sau khi đổi | `DESCRIBE`, `SELECT COUNT(*)` |
| 6 | Chạy thử bằng tay | `php artisan serve` |
| 7 | Chạy test | `php artisan test` (cần `pdo_sqlite`) |
| 8 | Nếu lỗi: khôi phục | `mysql ... < file.sql` |

Không chạy `php artisan migrate:fresh` hay `migrate:fresh --seed` trên database có dữ liệu thật. Hai lệnh này xóa toàn bộ bảng.

## 1. Nhánh thay đổi những gì

### Database

Có hai migration cần có trong database. Laravel chạy theo thứ tự tên file:

1. `2026_11_03_000001_add_question_generation.php` (phần Wikipedia và Gemini của nhóm, chạy trước):
   - tạo bảng `articles` và `ai_generation_logs`
   - thêm cột `article_id`, `source` vào `quiz_sets`
   - tạo sẵn một bài viết cho mỗi bộ đề cũ
2. `2026_11_04_000001_add_progress_to_quiz_attempts.php` (phần Quiz của nhánh này):
   - thêm 5 cột vào `quiz_attempts` (bảng bên dưới) và một index `(user_id, completed_at)`
   - đánh dấu mọi lượt làm bài cũ là đã hoàn thành: `completed_at = created_at`, `answering_started_at = created_at`
   - xóa dòng trùng trong `quiz_attempt_answers` (cùng `quiz_attempt_id` và `question_id`), giữ dòng có `id` nhỏ nhất
   - thêm khóa duy nhất `(quiz_attempt_id, question_id)` vào `quiz_attempt_answers`

Cột mới của `quiz_attempts`:

| Cột | Kiểu | Ý nghĩa |
|---|---|---|
| `reading_started_at` | `datetime(3)`, cho phép null | Lúc bắt đầu bước đọc tư liệu |
| `answering_started_at` | `datetime(3)`, cho phép null | Lúc bắt đầu làm bài. Null nghĩa là vẫn đang ở bước đọc |
| `current_question_id` | khóa ngoại tới `questions.id`, cho phép null | Câu đang hiển thị. Xóa câu hỏi thì cột này về null |
| `question_started_at` | `datetime(3)`, cho phép null | Lúc câu hiện tại bắt đầu. Server dùng mốc này để tính giờ và điểm |
| `completed_at` | `timestamp`, cho phép null | Lúc hoàn thành. Null nghĩa là lượt đang làm dở |

Các hằng số trong `app/Models/QuizAttempt.php`: bước đọc tối đa 180 giây, dung sai nhận đáp án 2 giây, lượt dở quá 120 phút thì coi như bỏ.

### Code

12 file Quiz của commit `0f3c1ec`: `QuizController`, `QuizAttempt`, `QuizAttemptAnswer`, migration `2026_11_04`, 4 view mới (`question`, `read`, `answer-result`, `result`), `mistakes.blade.php`, `routes/web.php`, `tests/Feature/QuizFlowTest.php`. File `play.blade.php` và route `quiz.submit` bị xóa.

Thêm một file ngoài phạm vi trên: `app/Http/Controllers/DashboardController.php`, thêm `->completed()` để Dashboard chỉ liệt kê lượt đã hoàn thành (xem mục 9).

## 2. Chuẩn bị

Lấy thông tin kết nối trong file `.env`: `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`. Đặt hai biến cho các lệnh bên dưới (thay bằng giá trị của bạn):

```bash
DB_NAME=ocean_quiz_db
DB_USER=root
```

Các lệnh dùng `-p` để MySQL hỏi mật khẩu. Không gõ mật khẩu thẳng vào dòng lệnh.

Kiểm tra kết nối:

```bash
mysql -u "$DB_USER" -p "$DB_NAME" -e "SELECT DATABASE(), VERSION();"
```

Yêu cầu phiên bản: MySQL ≥ 5.6.4 hoặc MariaDB ≥ 5.3 (cần kiểu `datetime(3)`), bảng dùng InnoDB (cần cho khóa dòng `lockForUpdate`).

## 3. Bước 1 — Sao lưu database

```bash
mkdir -p ~/oceanquiz-backup
mysqldump -u "$DB_USER" -p --single-transaction --routines --triggers "$DB_NAME" \
  > ~/oceanquiz-backup/${DB_NAME}_$(date +%Y%m%d_%H%M).sql
```

Kiểm tra file sao lưu có dùng được không:

```bash
ls -lh ~/oceanquiz-backup/
tail -n 1 ~/oceanquiz-backup/${DB_NAME}_*.sql        # phải có dòng "-- Dump completed on ..."
grep -c "CREATE TABLE" ~/oceanquiz-backup/${DB_NAME}_*.sql
```

Lưu ý:

- Để file sao lưu ngoài thư mục dự án. Dù vậy `.gitignore` đã chặn `*.sql`, `.env` nên sao lưu không bị đưa nhầm lên GitHub.
- File sao lưu có cả bảng `migrations`, nên khôi phục sẽ đưa cả danh sách migration đã chạy về như cũ.
- Phần migration xóa dòng trùng không hoàn tác được bằng `down()`. File sao lưu là cách duy nhất lấy lại các dòng đó.

## 4. Bước 2 — Ghi lại số liệu trước khi đổi

```bash
mysql -u "$DB_USER" -p "$DB_NAME" -e "
SELECT COUNT(*) AS so_luot_lam FROM quiz_attempts;
SELECT COUNT(*) AS so_cau_tra_loi FROM quiz_attempt_answers;
SELECT COUNT(*) AS cap_bi_trung FROM (
  SELECT 1 FROM quiz_attempt_answers
  GROUP BY quiz_attempt_id, question_id HAVING COUNT(*) > 1
) t;
SELECT SUM(total_score) AS tong_diem, SUM(quizzes_completed) AS tong_luot FROM users;
SELECT migration FROM migrations ORDER BY id;
"
```

Ghi lại 4 con số đầu. `cap_bi_trung` cho biết có dòng nào sẽ bị xóa ở bước migration. Danh sách `migrations` cho biết hai migration ở mục 1 đã chạy chưa.

## 5. Bước 3 — Lấy code nhánh

Làm trong worktree của nhóm, không làm ở thư mục `oceanquiz` (lịch sử git khác nhau):

```bash
cd ../oceanquiz-nhom12
git status                      # phải sạch hoặc chỉ có thay đổi bạn biết
git switch FixQuizLogic         # đã có nhánh local; nếu chưa: git switch -c FixQuizLogic --track myfork/FixQuizLogic
composer install
cp .env.example .env            # bỏ qua nếu đã có .env
php artisan key:generate        # bỏ qua nếu .env đã có APP_KEY
```

Sửa `.env` cho đúng database cần cập nhật (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`). `SESSION_DRIVER` mặc định là `database`, bảng `sessions` đã nằm trong migration `0001_01_01_000000`.

Giao diện nạp CSS từ `public/css/ocean-app.css`, không cần `npm install` hay `npm run build`.

## 6. Bước 4 — Chạy migration

Xem migration nào đang chờ:

```bash
php artisan migrate:status
```

Xem trước câu SQL sẽ chạy, không thay đổi gì:

```bash
php artisan migrate --pretend
```

Chạy thật:

```bash
php artisan migrate
```

Kết quả mong đợi: chỉ các migration ở mục 1 chưa có trong `migrations` được chạy, mỗi cái báo `DONE`. Migration đã chạy rồi sẽ không chạy lại.

Không cần chạy `php artisan db:seed` trên database đã có dữ liệu. Seeder tạo thêm bộ đề mẫu mỗi lần chạy, nên sẽ bị nhân đôi.

## 7. Bước 5 — Kiểm tra sau khi đổi

```bash
mysql -u "$DB_USER" -p "$DB_NAME" -e "
DESCRIBE quiz_attempts;
SHOW INDEX FROM quiz_attempt_answers WHERE Non_unique = 0;
SELECT COUNT(*) AS so_luot_lam FROM quiz_attempts;
SELECT COUNT(*) AS luot_chua_hoan_thanh FROM quiz_attempts WHERE completed_at IS NULL;
SELECT COUNT(*) AS so_cau_tra_loi FROM quiz_attempt_answers;
SELECT SUM(total_score) AS tong_diem, SUM(quizzes_completed) AS tong_luot FROM users;
"
```

Đối chiếu với số liệu ở bước 2:

| Kiểm tra | Kết quả đúng |
|---|---|
| `DESCRIBE quiz_attempts` | Thấy đủ 5 cột mới ở mục 1 |
| `SHOW INDEX` | Có khóa duy nhất trên `(quiz_attempt_id, question_id)` |
| `so_luot_lam` | Bằng trước khi đổi |
| `luot_chua_hoan_thanh` | 0 (chưa có ai làm bài mới) |
| `so_cau_tra_loi` | Bằng số trước khi đổi, trừ tổng số dòng trùng đã bị xóa |
| `tong_diem`, `tong_luot` | Không đổi. Migration không đụng bảng `users` |

Nếu một con số lệch, dừng lại và khôi phục (mục 10).

## 8. Bước 6 — Chạy thử bằng tay

```bash
php artisan serve
```

Mở `http://127.0.0.1:8000` và kiểm tra theo thứ tự:

1. Đăng nhập. Dashboard hiện lịch sử làm bài cũ với điểm và số câu đúng như trước.
2. Bấm vào làm Quiz (`/quiz/play`): ra trang đọc tư liệu có đếm ngược, tối đa 180 giây.
3. Bấm "Bắt đầu làm bài": ra câu 1 có 4 ô đáp án và đồng hồ.
4. Chọn đáp án, bấm "Trả lời": ra trang phản hồi đúng/sai và giải thích. Bấm lại nút nhiều lần không được tính điểm hai lần.
5. Tải lại trang (F5) giữa chừng: vẫn đúng câu đó, đồng hồ không bị đặt lại.
6. Làm hết các câu: ra trang kết quả. Điểm tích lũy trên bảng xếp hạng chỉ tăng một lần.
7. Mở lại Dashboard: lượt vừa làm xong hiện lên. Lượt đang làm dở không hiện.
8. Mở "Ôn tập câu sai": hiện câu sai của các lượt đã hoàn thành.
9. Đăng nhập tài khoản admin, xuất bản một bộ đề nháp: bộ đề đó có thể được chọn ở `/quiz/play`.

## 9. Ảnh hưởng tới các phần khác

- **Dashboard** (`DashboardController`): trước đây lượt làm bài chỉ được tạo khi nộp bài. Nay lượt được tạo ngay khi vào `/quiz/play`. Nếu không lọc thì lượt dở sẽ hiện "+0 điểm" trên Dashboard. Dòng `->completed()` đã xử lý.
- **Admin**: không cho sửa hoặc xóa bộ đề đã có lượt làm (`QuestionSetController`), và cột "lượt" đếm cả lượt dở. Bộ đề có người mở nhưng chưa làm xong sẽ bị khóa như bộ đề đã có người làm. Dữ liệu không hỏng, chỉ khác hành vi.
- **Bảng xếp hạng và điểm tích lũy**: `users.total_score` và `users.quizzes_completed` được cộng đúng một lần khi hoàn thành lượt, như trước.
- **Route**: `quiz.play`, `quiz.mistakes` giữ nguyên. Chỉ `quiz.submit` bị bỏ. Không file nào khác tham chiếu tới route này.

## 10. Khôi phục khi gặp lỗi

### Cách 1 (khuyến nghị): khôi phục từ file sao lưu

```bash
mysql -u "$DB_USER" -p "$DB_NAME" < ~/oceanquiz-backup/<ten_file_sao_luu>.sql
```

File sao lưu có lệnh xóa rồi tạo lại từng bảng, nên lệnh này ghi đè các bảng trong file. Sau đó đổi về code cũ nếu cần: `git switch feature/quiz`.

### Cách 2: hoàn tác migration

```bash
php artisan migrate:rollback --step=1
```

Lệnh này chỉ hoàn tác migration chạy gần nhất (`2026_11_04`): bỏ 5 cột, index và khóa duy nhất. Các dòng trùng đã bị xóa không quay lại. Các lượt đang làm dở cũng mất trạng thái. Không rollback `2026_11_03`, vì nó xóa bảng `articles` và `ai_generation_logs`.

## 11. Tạo database mới hoàn toàn (chỉ để thử, không có dữ liệu thật)

```bash
mysql -u "$DB_USER" -p -e "CREATE DATABASE ocean_quiz_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Đổi `DB_DATABASE=ocean_quiz_test` trong `.env`, rồi:

```bash
php artisan migrate --seed
```

Seeder tạo tài khoản mẫu và hai bộ đề mẫu (xem `database/seeders/DatabaseSeeder.php`). Chỉ chạy `--seed` trên database trống.

## 12. Chạy test

Test dùng SQLite trong bộ nhớ (cấu hình ở `phpunit.xml`), không đụng database thật. Cần bật `pdo_sqlite` và `sqlite3` cho PHP:

```bash
php -m | grep -i sqlite       # phải thấy pdo_sqlite và sqlite3
```

Nếu chưa có, trên Arch/Omarchy: `sudo pacman -S php-sqlite`, rồi thêm hai dòng `extension=pdo_sqlite` và `extension=sqlite3` vào một file `.ini` trong `/etc/php/conf.d/` nếu chưa tự bật. Sau đó:

```bash
php artisan test --filter=QuizFlowTest
php artisan test
```

Cảnh báo: `QuizFlowTest` dùng `DatabaseMigrations`, nghĩa là mỗi test chạy `migrate:fresh` trên database của môi trường test. Vì vậy:

- không `export DB_CONNECTION=...` hoặc `DB_DATABASE=...` trong terminal khi chạy test
- không sửa `phpunit.xml` sang MySQL thật

Test chạy trên SQLite nên không kiểm tra được hành vi riêng của MySQL (khóa dòng, `datetime(3)`, câu DELETE có truy vấn con). Vì vậy vẫn phải chạy thử bằng tay ở mục 8 trên MySQL.

## 13. Lỗi thường gặp

| Thông báo | Nguyên nhân | Cách xử lý |
|---|---|---|
| `Base table or view already exists` khi migrate | Database có sẵn bảng nhưng bảng `migrations` không ghi nhận | Dùng database mới (mục 11), hoặc khôi phục sao lưu rồi thử lại |
| `Unknown column 'completed_at'` | Chưa chạy migration `2026_11_04` | `php artisan migrate` |
| `could not find driver` khi chạy test | PHP thiếu `pdo_sqlite` | Làm theo mục 12 |
| `SQLSTATE[HY000] [1045] Access denied` | Sai `DB_USERNAME` hoặc `DB_PASSWORD` trong `.env` | Sửa `.env` rồi chạy `php artisan config:clear` |
| `SQLSTATE[HY000] [2002] Connection refused` | MySQL/MariaDB chưa chạy | Khởi động dịch vụ MySQL/MariaDB |
| `No application encryption key has been specified` | Thiếu `APP_KEY` | `php artisan key:generate` |
| Lỗi trùng khóa khi thêm khóa duy nhất | Có dòng trùng nhưng bước xóa chưa chạy hết | Khôi phục sao lưu, kiểm tra lại cặp trùng ở bước 2, chạy lại migration |

## 14. Sau khi tích hợp xong

Trong worktree `../oceanquiz-nhom12`:

```bash
git status                 # phải thấy HuongDanTichHop.md và DashboardController.php đã được add
git diff --cached          # xem lại nội dung sắp commit
```

Khi hài lòng thì commit, đẩy nhánh lên fork rồi mở Pull Request vào repo `luanpnm65/Nhom12_Web_Kien_Thuc_Bien`. Trong mô tả Pull Request, ghi rõ hai điểm: có thêm một dòng ở `DashboardController`, và cần chạy migration `2026_11_04` kèm sao lưu trước khi chạy.

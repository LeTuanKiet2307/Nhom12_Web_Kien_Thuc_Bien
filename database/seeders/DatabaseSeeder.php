<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Question;
use App\Models\QuizSet;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@ocean.io'],
            [
                'name' => 'Thuyền Trưởng Nemo (Admin)',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'total_score' => 14500,
                'quizzes_completed' => 4,
            ]
        );

        User::updateOrCreate(
            ['email' => 'explorer@ocean.io'],
            [
                'name' => 'Sylvia Earle',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'total_score' => 18200,
                'quizzes_completed' => 5,
            ]
        );

        User::updateOrCreate(
            ['email' => 'cousteau@ocean.io'],
            [
                'name' => 'Jacques Cousteau',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'total_score' => 15900,
                'quizzes_completed' => 4,
            ]
        );

        // Bộ đề #1: Rãnh Mariana
        $set1 = QuizSet::create([
            'title' => 'Bí Ẩn Rãnh Mariana & Vực Thẳm Challenger Deep',
            'topic' => 'Rãnh Mariana',
            'ocean_zone' => 'Tầng Hố Thẳm (6,000m - 11,000m)',
            'source_url' => 'https://vi.wikipedia.org/wiki/R%C3%A3nh_Mariana',
            'is_active' => true,
            'created_by' => $admin->id,
            'reading_passage' => "Rãnh Mariana là rãnh đại dương sâu nhất trên Trái Đất, nằm ở khu vực tây Thái Bình Dương, cách quần đảo Mariana khoảng 200 km về phía đông. Rãnh có hình lưỡi liềm với chiều dài khoảng 2.550 km và chiều rộng trung bình khoảng 69 km.\n\nĐiểm sâu nhất của rãnh Mariana được gọi là vực Challenger Deep, với độ sâu đo được lên tới 10.984 ± 25 mét. Tại đáy vực này, cột nước khổng lồ phía trên tạo ra áp suất lên tới 1.086 bar — tức là lớn hơn 1.071 lần so với áp suất khí quyển tiêu chuẩn trên mặt nước biển. Ở mức áp suất cực đại này, mật độ của nước biển bị nén tăng thêm 4,96%, và nhiệt độ nước ở đáy dao động từ 1 đến 4 °C.\n\nBất chấp điều kiện khắc nghiệt, sự sống vẫn tồn tại ở Challenger Deep. Các đoàn thám hiểm đã phát hiện ra trùng chân giả đơn bào khổng lồ (Xenophyophores), giáp xác chân khác (Amphipod), hải sâm và đặc biệt là loài cá sên Mariana (Pseudoliparis swirei) — loài cá giữ kỷ lục sống sâu nhất từng được ghi nhận ở độ sâu khoảng 8.000 mét, bởi tế bào của cá xương không thể dùng phân tử TMAO để bảo vệ protein dưới độ sâu quá 8.400 mét.",
        ]);

        $questionsSet1 = [
            [
                'question_text' => 'Điểm sâu nhất bên trong Rãnh Mariana có tên gọi là gì?',
                'options' => ['Vực Horizon Deep', 'Vực Challenger Deep', 'Vực Sirena Deep', 'Rãnh Tonga'],
                'correct_option_index' => 1,
                'explanation' => 'Vực Challenger Deep nằm ở phía nam của Rãnh Mariana với độ sâu lên tới khoảng 10.984 mét.',
                'time_limit' => 20,
            ],
            [
                'question_text' => 'Áp suất nước tại đáy Rãnh Mariana lớn gấp khoảng bao nhiêu lần so với áp suất khí quyển trên mặt biển?',
                'options' => ['Khoảng 100 lần', 'Khoảng 500 lần', 'Hơn 1.070 lần', 'Hơn 10.000 lần'],
                'correct_option_index' => 2,
                'explanation' => 'Áp suất tại đáy đạt 1.086 bar, lớn hơn 1.071 lần so với áp suất khí quyển tiêu chuẩn.',
                'time_limit' => 20,
            ],
            [
                'question_text' => 'Nhiệt độ nước biển ở dưới đáy Rãnh Mariana dao động trong khoảng nào?',
                'options' => ['-15 đến -5 °C', '1 đến 4 °C', '15 đến 20 °C', 'Đóng băng hoàn toàn ở 0 °C'],
                'correct_option_index' => 1,
                'explanation' => 'Nhiệt độ nước ở đáy vực Challenger Deep luôn duy trì ở mức lạnh giá từ 1 đến 4 °C.',
                'time_limit' => 20,
            ],
            [
                'question_text' => 'Loài cá nào đang giữ kỷ lục là một trong những loài cá sống sâu nhất ở Rãnh Mariana (~8.000m)?',
                'options' => ['Cá Vây Tay Cổ Đại', 'Cá Sên Mariana (Mariana Snailfish)', 'Mực Ma Cà Rồng', 'Cá Mập Yêu Tinh'],
                'correct_option_index' => 1,
                'explanation' => 'Cá sên Mariana (Pseudoliparis swirei) thích nghi hoàn hảo ở độ sâu khoảng 8.000 mét.',
                'time_limit' => 20,
            ],
            [
                'question_text' => 'Vì sao các loài cá xương không thể sống sót ở độ sâu dưới 8.400 mét?',
                'options' => [
                    'Vì dưới 8.400m hoàn toàn không còn oxy hòa tan',
                    'Vì phân tử TMAO trong tế bào không còn đủ khả năng bảo vệ protein trước áp suất quá lớn',
                    'Vì nước biển đặc cứng lại không thể bơi',
                    'Vì nhiệt độ nước sôi trên 100 °C ở khắp mọi nơi',
                ],
                'correct_option_index' => 1,
                'explanation' => 'Cá sử dụng hợp chất TMAO để giữ cấu trúc protein không bị biến dạng dưới áp suất cao, nhưng giới hạn sinh học của TMAO dừng lại ở khoảng 8.400 mét.',
                'time_limit' => 20,
            ],
        ];

        foreach ($questionsSet1 as $q) {
            Question::create(array_merge($q, ['quiz_set_id' => $set1->id]));
        }

        // Bộ đề #2: Phát quang sinh học
        $set2 = QuizSet::create([
            'title' => 'Hiện Tượng Phát Quang Sinh Học Ở Biển Sâu',
            'topic' => 'Phát quang sinh học',
            'ocean_zone' => 'Tầng Nửa Đêm (1,000m - 4,000m)',
            'source_url' => 'https://vi.wikipedia.org/wiki/Ph%C3%A1t_quang_sinh_h%E1%BB%8Dc',
            'is_active' => true,
            'created_by' => $admin->id,
            'reading_passage' => "Phát quang sinh học (Bioluminescence) là quá trình tạo ra và phát xạ ánh sáng của các cơ thể sống. Tại Tầng Chạng Vạng (200m–1.000m) và Tầng Nửa Đêm (1.000m–4.000m) của đại dương, các nhà hải dương học ước tính có hơn 76% loài động vật biển có khả năng tự phát sáng để săn mồi, tự vệ hoặc giao tiếp.\n\nPhản ứng hóa học tạo ra ánh sáng sinh học đòi hỏi hai hợp chất chính: phân tử phát sáng gọi là Luciferin và enzyme xúc tác gọi là Luciferase. Khi Luciferin phản ứng với oxy dưới sự xúc tác của Luciferase, năng lượng được giải phóng dưới dạng 'ánh sáng lạnh' (nghĩa là dưới 20% năng lượng bị tiêu hao thành nhiệt).\n\nHầu hết ánh sáng phát quang dưới biển nằm trong dải màu xanh lam và xanh lục (bước sóng từ 440 đến 490 nanomet) vì các bước sóng này truyền đi xa nhất trong môi trường nước biển. Tuy nhiên, loài cá rồng biển sâu thuộc chi Malacosteus (Stoplight Loosejaw) lại tiến hóa khả năng phát ra ánh sáng đỏ và hồng ngoại cực hiếm, giúp chúng soi sáng con mồi trong bóng tối mà con mồi không hề hay biết.",
        ]);

        $questionsSet2 = [
            [
                'question_text' => 'Hai thành phần hóa sinh chính cần thiết để tạo ra phản ứng phát quang sinh học ở sinh vật biển là gì?',
                'options' => [
                    'Diệp lục (Chlorophyll) và Carotene',
                    'Luciferin và enzyme Luciferase',
                    'Hemoglobin và Melanin',
                    'Collagen và Keratin',
                ],
                'correct_option_index' => 1,
                'explanation' => 'Phân tử Luciferin bị oxy hóa dưới sự xúc tác của enzyme Luciferase để giải phóng ánh sáng.',
                'time_limit' => 20,
            ],
            [
                'question_text' => 'Tại sao phần lớn sinh vật biển sâu lại phát ra ánh sáng màu xanh lam và xanh lục (440–490 nm)?',
                'options' => [
                    'Vì bước sóng xanh lam - xanh lục truyền đi xa nhất trong nước biển',
                    'Vì sinh vật biển không có gen tạo màu khác',
                    'Vì ánh sáng xanh giúp làm ấm vùng nước đóng băng',
                    'Vì kẻ săn mồi bị mù hoàn toàn với ánh sáng xanh',
                ],
                'correct_option_index' => 0,
                'explanation' => 'Nước biển hấp thụ rất nhanh các bước sóng dài (như màu đỏ, cam), trong khi bước sóng xanh lam truyền đi xa nhất.',
                'time_limit' => 20,
            ],
            [
                'question_text' => 'Vì sao phát quang sinh học thường được gọi là "ánh sáng lạnh"?',
                'options' => [
                    'Vì nó chỉ hoạt động ở vùng nước dưới 4 °C',
                    'Vì chưa tới 20% năng lượng ánh sáng phát ra chuyển hóa thành nhiệt',
                    'Vì nó làm đóng băng các phân tử nước xung quanh',
                    'Vì nó cần tinh thể băng để phản chiếu',
                ],
                'correct_option_index' => 1,
                'explanation' => 'Hiệu suất phát sáng sinh học cực cao, dưới 20% năng lượng bị thất thoát dưới dạng nhiệt năng.',
                'time_limit' => 20,
            ],
            [
                'question_text' => 'Loài cá săn mồi biển sâu nào có khả năng phát ra ánh sáng đỏ như một chiếc "ống nhòm hồng ngoại" bí mật?',
                'options' => [
                    'Cá Voi Xanh',
                    'Cá Rồng Đèn Đỏ (Malacosteus / Stoplight Loosejaw)',
                    'Cá Hề',
                    'Sứa Bờm Sư Tử',
                ],
                'correct_option_index' => 1,
                'explanation' => 'Cá rồng chi Malacosteus phát ra ánh sáng đỏ để nhìn rõ con mồi trong khi các loài sinh vật biển sâu khác không nhìn thấy ánh sáng đỏ.',
                'time_limit' => 20,
            ],
            [
                'question_text' => 'Theo ước tính của các nhà nghiên cứu, khoảng bao nhiêu phần trăm động vật biển ở tầng nước sâu có khả năng phát quang sinh học?',
                'options' => ['Dưới 5%', 'Khoảng 25%', 'Hơn 76%', 'Chính xác 100%'],
                'correct_option_index' => 2,
                'explanation' => 'Hơn 76% sinh vật ở cột nước tầng trung và tầng sâu có khả năng tự phát sáng.',
                'time_limit' => 20,
            ],
        ];

        foreach ($questionsSet2 as $q) {
            Question::create(array_merge($q, ['quiz_set_id' => $set2->id]));
        }

        foreach ([$set1, $set2] as $set) {
            $article = Article::create(['title' => $set->title, 'content' => $set->reading_passage, 'source_url' => $set->source_url]);
            $set->update(['article_id' => $article->id]);
        }
    }
}

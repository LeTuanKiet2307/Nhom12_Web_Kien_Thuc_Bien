<?php

namespace App\Services;

use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WikipediaService
{
    private function query(array $parameters): array
    {
        try {
            $response = Http::acceptJson()->withUserAgent(config('services.wikipedia.user_agent'))
                ->connectTimeout(8)->timeout(20)->get('https://vi.wikipedia.org/w/api.php', $parameters + [
                    'action' => 'query', 'format' => 'json', 'formatversion' => 2, 'maxlag' => 5,
                ])->throw()->json();
        } catch (HttpClientException $e) {
            throw new RuntimeException('Chưa kết nối được Wikipedia. Hãy thử lại hoặc nhập bài thủ công.');
        }
        if (! is_array($response) || isset($response['error'])) {
            throw new RuntimeException('Wikipedia chưa trả về dữ liệu hợp lệ. Vui lòng thử lại sau.');
        }

        return $response;
    }

    public function search(string $keyword): array
    {
        $data = $this->query(['list' => 'search', 'srsearch' => $keyword, 'srnamespace' => 0, 'srlimit' => 10]);

        return array_map(fn ($page) => [
            'id' => (int) $page['pageid'], 'title' => $page['title'],
            'snippet' => html_entity_decode(strip_tags($page['snippet'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ], $data['query']['search'] ?? []);
    }

    public function article(int $pageId): array
    {
        $data = $this->query(['prop' => 'extracts', 'explaintext' => 1, 'pageids' => $pageId]);
        $page = $data['query']['pages'][0] ?? [];
        if (isset($page['missing']) || empty($page['extract']) || ($page['ns'] ?? 0) !== 0) {
            throw new RuntimeException('Bài này không có nội dung văn bản phù hợp để lưu.');
        }

        return [
            'wiki_page_id' => $pageId, 'title' => mb_substr($page['title'], 0, 200),
            'content' => mb_substr($page['extract'], 0, 100000),
            'source_name' => mb_substr('Wikipedia tiếng Việt · '.$page['title'], 0, 255),
            'source_url' => 'https://vi.wikipedia.org/?curid='.$pageId,
            'license' => 'CC BY-SA · xem giấy phép và lịch sử tác giả tại bài nguồn',
        ];
    }
}

{{--
    Nhãn tầng đại dương kèm chấm màu.
    Tham số: $zone (chuỗi ocean_zone), $short (bỏ phần độ sâu trong ngoặc nếu true).
--}}
@php
    $zoneLabel = trim((string) ($zone ?? ''));
    $zoneLower = mb_strtolower($zoneLabel);
    $zoneNumber = 1;

    if (str_contains($zoneLower, 'hố thẳm') || str_contains($zoneLower, 'rãnh')) {
        $zoneNumber = 5;
    } elseif (str_contains($zoneLower, 'đáy thẳm')) {
        $zoneNumber = 4;
    } elseif (str_contains($zoneLower, 'nửa đêm')) {
        $zoneNumber = 3;
    } elseif (str_contains($zoneLower, 'chạng vạng')) {
        $zoneNumber = 2;
    } elseif (preg_match('/(\d[\d.,]*)\s*m/i', $zoneLabel, $depthMatch) === 1) {
        $depth = (int) preg_replace('/\D/', '', $depthMatch[1]);
        $zoneNumber = $depth >= 6000 ? 5 : ($depth >= 4000 ? 4 : ($depth >= 1000 ? 3 : ($depth >= 200 ? 2 : 1)));
    }

    if (! empty($short) && str_contains($zoneLabel, '(')) {
        $zoneLabel = trim(explode('(', $zoneLabel, 2)[0]);
    }
@endphp
<span class="zone"><span class="zone__dot zone__dot--{{ $zoneNumber }}" aria-hidden="true"></span>{{ $zoneLabel }}</span>

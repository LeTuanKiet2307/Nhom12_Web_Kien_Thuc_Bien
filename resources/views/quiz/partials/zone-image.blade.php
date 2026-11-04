{{--
    Ảnh minh hoạ theo tầng đại dương kèm chú thích và nguồn ảnh.
    Tham số: $quizSet (bắt buộc), $variant ('read' mặc định | 'result').
--}}
@php
    $zoneText = trim((string) ($quizSet->ocean_zone ?? ''));
    $zoneLower = mb_strtolower($zoneText);
    $variant = $variant ?? 'read';

    $zoneNumber = 1;

    if (str_contains($zoneLower, 'hố thẳm') || str_contains($zoneLower, 'rãnh')) {
        $zoneNumber = 5;
    } elseif (str_contains($zoneLower, 'đáy thẳm')) {
        $zoneNumber = 4;
    } elseif (str_contains($zoneLower, 'nửa đêm')) {
        $zoneNumber = 3;
    } elseif (str_contains($zoneLower, 'chạng vạng')) {
        $zoneNumber = 2;
    } elseif (preg_match('/(\d[\d.,]*)\s*m/i', $zoneText, $depthMatch) === 1) {
        $depth = (int) preg_replace('/\D/', '', $depthMatch[1]);
        $zoneNumber = $depth >= 6000 ? 5 : ($depth >= 4000 ? 4 : ($depth >= 1000 ? 3 : ($depth >= 200 ? 2 : 1)));
    }

    $zoneImages = [
        1 => [
            'file' => 'images/turtle.jpg',
            'alt' => 'Rùa xanh Chelonia mydas bơi trong làn nước xanh',
            'caption' => 'Rùa xanh (Chelonia mydas) — loài rùa biển quen thuộc ở vùng nước ấm, thường gặp ở tầng ánh sáng.',
            'credit' => 'Diego Delso, CC BY-SA 4.0',
        ],
        2 => [
            'file' => 'images/lanternfish.jpg',
            'alt' => 'Cá đèn lồng Benthosema glaciale phát sáng trong bóng tối',
            'caption' => 'Cá đèn lồng (Benthosema glaciale) — loài cá nhỏ phát quang sống ở tầng chạng vạng.',
            'credit' => 'HulloThere, CC BY 4.0',
        ],
        3 => $variant === 'result'
            ? [
                'file' => 'images/dumbo.jpg',
                'alt' => 'Bạch tuộc Dumbo Grimpoteuthis sp. bơi trong vùng biển sâu',
                'caption' => 'Grimpoteuthis sp. chụp bằng ROV ở vùng biển sâu.',
                'credit' => 'NOAA Okeanos Explorer, phạm vi công cộng',
            ]
            : [
                'file' => 'images/dumbo-discoveryi.jpg',
                'alt' => 'Bạch tuộc Dumbo Grimpoteuthis discoveryi với hai vây hình tai',
                'caption' => 'Grimpoteuthis discoveryi — hai vây hình tai nhìn rõ ở hai bên đỉnh thân.',
                'credit' => 'Mike Vecchione, NOAA, phạm vi công cộng',
            ],
        4 => [
            'file' => 'images/bathypterois.jpg',
            'alt' => 'Cá ba chân Bathypterois sp. đứng trên đáy biển sâu',
            'caption' => 'Cá ba chân (Bathypterois sp.) — loài cá dùng vây làm giá đỡ để đứng trên đáy thẳm.',
            'credit' => 'NOAA, North Atlantic Stepping Stones, phạm vi công cộng',
        ],
        5 => [
            'file' => 'images/riftia.jpg',
            'alt' => 'Giun ống khổng lồ Riftia pachyptila quanh miệng phun thủy nhiệt',
            'caption' => 'Giun ống khổng lồ (Riftia pachyptila) — sống quanh miệng phun thủy nhiệt ở hố thẳm.',
            'credit' => 'Ifremer, CC BY 4.0',
        ],
    ];

    $image = $zoneImages[$zoneNumber];
@endphp
<figure class="zone-figure zone-figure--{{ $variant }}">
    <img src="{{ asset($image['file']) }}" alt="{{ $image['alt'] }}" width="600" height="{{ $variant === 'result' ? 450 : 400 }}" loading="lazy">
    <figcaption class="zone-figure__caption">
        {{ $image['caption'] }}
        <span class="zone-figure__credit">Ảnh: {{ $image['credit'] }}</span>
    </figcaption>
</figure>

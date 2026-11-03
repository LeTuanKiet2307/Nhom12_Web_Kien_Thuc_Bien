@extends('layouts.app')

@section('title', 'Khám Phá Độ Sâu Đại Dương | AbyssalMind')

@section('content')
<div class="ocean-scroll-wrapper" id="oceanWrapper">

    <!-- Thanh đo độ sâu cố định bên trái -->
    <aside class="depth-sidebar">
        <div class="depth-readout-box">
            <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Độ Sâu Sonar Hiện Tại</div>
            <div class="depth-meter-number" id="currentDepthDisplay">0m</div>
            <div class="depth-zone-badge" id="currentZoneName">Tầng Ánh Sáng</div>
        </div>

        <ul class="depth-scale-list">
            <li class="depth-scale-item active" data-target="zone-0" onclick="scrollToZone('zone-0')">
                <strong>0m</strong> — Tầng Ánh Sáng
            </li>
            <li class="depth-scale-item" data-target="zone-200" onclick="scrollToZone('zone-200')">
                <strong>200m</strong> — Tầng Chạng Vạng
            </li>
            <li class="depth-scale-item" data-target="zone-1000" onclick="scrollToZone('zone-1000')">
                <strong>1,000m</strong> — Tầng Nửa Đêm
            </li>
            <li class="depth-scale-item" data-target="zone-4000" onclick="scrollToZone('zone-4000')">
                <strong>4,000m</strong> — Tầng Đáy Thẳm
            </li>
            <li class="depth-scale-item" data-target="zone-6000" onclick="scrollToZone('zone-6000')">
                <strong>6,000m+</strong> — Rãnh Mariana
            </li>
        </ul>
    </aside>

    <!-- TẦNG 1: 0m TẦNG ÁNH SÁNG -->
    <section class="ocean-zone-section" id="zone-0" data-depth="0" data-zone="Tầng Ánh Sáng (0m - 200m)">
        <div class="zone-card">
            <div class="zone-header">
                <span class="zone-depth-pill">ĐỘ SÂU: 0m – 200m</span>
                <span style="color: #bae6fd; font-weight: 600;">☀️ Quang Hợp & Rạn San Hô</span>
            </div>
            <h1 style="font-size: 2.3rem; margin-bottom: 1rem;">Tầng Ánh Sáng (Epipelagic Zone)</h1>
            <p style="color: #e0f2fe; font-size: 1.05rem;">
                Chào mừng bạn đến với tầng mặt của đại dương — nơi ánh sáng mặt trời chiếu rọi rực rỡ, thúc đẩy quá trình quang hợp của thực vật phù du (nguồn tạo ra hơn 50% lượng oxy cho Trái Đất và nuôi sống 90% sinh vật biển).
            </p>
            <div class="creature-showcase">
                <div class="creature-box">
                    <div style="font-size: 2rem;">🐢</div>
                    <strong>Rùa Biển Xanh (Vích)</strong>
                    <span style="font-size: 0.85rem; color: #cbd5e1;">Định hướng vượt hàng ngàn hải lý qua đại dương nhờ cảm nhận từ trường Trái Đất.</span>
                </div>
                <div class="creature-box">
                    <div style="font-size: 2rem;">🐬</div>
                    <strong>Cá Heo Mũi Chai</strong>
                    <span style="font-size: 0.85rem; color: #cbd5e1;">Sử dụng hệ thống định vị bằng tiếng vang sinh học (Echolocation) để săn mồi và giao tiếp.</span>
                </div>
                <div class="creature-box">
                    <div style="font-size: 2rem;">🦈</div>
                    <strong>Cá Mập Trắng Lớn</strong>
                    <span style="font-size: 0.85rem; color: #cbd5e1;">Phát hiện xung điện từ cực nhỏ từ nhịp tim con mồi nhờ cơ quan Ampullae of Lorenzini.</span>
                </div>
            </div>
        </div>
    </section>

    <!-- TẦNG 2: 200m TẦNG CHẠNG VẠNG -->
    <section class="ocean-zone-section" id="zone-200" data-depth="200" data-zone="Tầng Chạng Vạng (200m - 1000m)">
        <div class="zone-card">
            <div class="zone-header">
                <span class="zone-depth-pill">ĐỘ SÂU: 200m – 1,000m</span>
                <span style="color: #7dd3fc; font-weight: 600;">🌗 Di Cư Thẳng Đứng Hàng Đêm</span>
            </div>
            <h2 style="font-size: 2.1rem; margin-bottom: 1rem;">Tầng Chạng Vạng (Mesopelagic Zone)</h2>
            <p style="color: #cbd5e1; font-size: 1.05rem;">
                Ánh sáng mặt trời yếu dần thành một màu xanh lam mờ ảo, không còn đủ cho quá trình quang hợp. Mỗi đêm, hàng ngàn tỷ sinh vật từ tầng này bơi lên mặt nước kiếm ăn, tạo nên cuộc di cư sinh học lớn nhất hành tinh.
            </p>
            <div class="creature-showcase">
                <div class="creature-box">
                    <div style="font-size: 2rem;">🦑</div>
                    <strong>Mực Thủy Tinh (Cockatoo Squid)</strong>
                    <span style="font-size: 0.85rem; color: #94a3b8;">Cơ thể trong suốt như pha lê giúp ngụy trang hoàn hảo trước kẻ săn mồi.</span>
                </div>
                <div class="creature-box">
                    <div style="font-size: 2rem;">🐟</div>
                    <strong>Cá Đèn Lồng (Lanternfish)</strong>
                    <span style="font-size: 0.85rem; color: #94a3b8;">Chiếm tới 65% tổng sinh khối cá biển sâu; phát sáng ở bụng để xóa bóng của chính mình.</span>
                </div>
                <div class="creature-box">
                    <div style="font-size: 2rem;">🐋</div>
                    <strong>Cá Nhà Táng Lặn Sâu</strong>
                    <span style="font-size: 0.85rem; color: #94a3b8;">Có thể nín thở suốt 90 phút để lặn xuống tầng chạng vạng săn mực khổng lồ.</span>
                </div>
            </div>
        </div>
    </section>

    <!-- TẦNG 3: 1000m TẦNG NỬA ĐÊM -->
    <section class="ocean-zone-section" id="zone-1000" data-depth="1000" data-zone="Tầng Nửa Đêm (1000m - 4000m)">
        <div class="zone-card">
            <div class="zone-header">
                <span class="zone-depth-pill">ĐỘ SÂU: 1,000m – 4,000m</span>
                <span style="color: #38bdf8; font-weight: 600;">🌌 Bóng Tối Vĩnh Cửu & Phát Quang Sinh Học</span>
            </div>
            <h2 style="font-size: 2.1rem; margin-bottom: 1rem;">Tầng Nửa Đêm (Bathypelagic Zone)</h2>
            <p style="color: #cbd5e1; font-size: 1.05rem;">
                Không một tia sáng mặt trời nào chạm tới độ sâu 1.000 mét. Ánh sáng duy nhất tại đây được tạo ra bởi phản ứng hóa học phát quang sinh học (Bioluminescence). Áp suất nước vượt ngưỡng 100 lần khí quyển và nhiệt độ chỉ khoảng 4 °C.
            </p>
            <div class="creature-showcase">
                <div class="creature-box">
                    <div style="font-size: 2rem;">🏮</div>
                    <strong>Cá Vây Chân (Anglerfish)</strong>
                    <span style="font-size: 0.85rem; color: #94a3b8;">Treo chiếc "cần câu" phát sáng chứa vi khuẩn cộng sinh ngay trước miệng để dụ con mồi.</span>
                </div>
                <div class="creature-box">
                    <div style="font-size: 2rem;">🧛</div>
                    <strong>Mực Ma Cà Rồng (Vampire Squid)</strong>
                    <span style="font-size: 0.85rem; color: #94a3b8;">Sống trong vùng tối thiểu oxy và ăn "tuyết biển" (mùn hữu cơ rơi từ tầng trên xuống).</span>
                </div>
                <div class="creature-box">
                    <div style="font-size: 2rem;">🐉</div>
                    <strong>Cá Rồng Đèn Đỏ (Stoplight Loosejaw)</strong>
                    <span style="font-size: 0.85rem; color: #94a3b8;">Phát ra tia sáng đỏ/hồng ngoại như ống nhòm hồng ngoại mà con mồi không thể nhìn thấy.</span>
                </div>
            </div>
        </div>
    </section>

    <!-- TẦNG 4: 4000m TẦNG ĐÁY THẲM -->
    <section class="ocean-zone-section" id="zone-4000" data-depth="4000" data-zone="Tầng Đáy Thẳm (4000m - 6000m)">
        <div class="zone-card">
            <div class="zone-header">
                <span class="zone-depth-pill">ĐỘ SÂU: 4,000m – 6,000m</span>
                <span style="color: #93c5fd; font-weight: 600;">🌋 Miệng Phun Thủy Nhiệt & Hóa Tổng Hợp</span>
            </div>
            <h2 style="font-size: 2.1rem; margin-bottom: 1rem;">Tầng Đáy Thẳm (Abyssopelagic Zone)</h2>
            <p style="color: #cbd5e1; font-size: 1.05rem;">
                Bao phủ hơn 60% bề mặt Trái Đất, đồng bằng đáy thẳm là vùng bùn lạnh giá. Tuy nhiên, xung quanh các miệng phun thủy nhiệt núi lửa ("Ống khói đen"), sự sống lại bùng nổ nhờ quá trình hóa tổng hợp (Chemosynthesis) thay vì ánh sáng mặt trời.
            </p>
            <div class="creature-showcase">
                <div class="creature-box">
                    <div style="font-size: 2rem;">🐙</div>
                    <strong>Bạch Tuộc Dumbo</strong>
                    <span style="font-size: 0.85rem; color: #94a3b8;">Loài bạch tuộc sống sâu nhất hành tinh, bơi lượn bằng hai chiếc vây giống tai voi Dumbo.</span>
                </div>
                <div class="creature-box">
                    <div style="font-size: 2rem;">🧪</div>
                    <strong>Giun Ống Khổng Lồ (Riftia)</strong>
                    <span style="font-size: 0.85rem; color: #94a3b8;">Không có miệng và ruột; sống nhờ vi khuẩn chuyển hóa khí độc Hydro Sunfua từ miệng núi lửa.</span>
                </div>
                <div class="creature-box">
                    <div style="font-size: 2rem;">🔭</div>
                    <strong>Cá Ba Chân (Tripod Fish)</strong>
                    <span style="font-size: 0.85rem; color: #94a3b8;">Đứng bất động trên lớp bùn đáy biển bằng 3 tia vây dài cứng như kiềng ba chân.</span>
                </div>
            </div>
        </div>
    </section>

    <!-- TẦNG 5: 6000m+ RÃNH MARIANA -->
    <section class="ocean-zone-section" id="zone-6000" data-depth="10984" data-zone="Tầng Hố Thẳm (Rãnh Mariana)">
        <div class="zone-card" style="border-color: var(--cyan-glow);">
            <div class="zone-header">
                <span class="zone-depth-pill">ĐỘ SÂU: 6,000m – 10,984m</span>
                <span style="color: var(--cyan-glow); font-weight: 700;">⚓ Đã Chạm Đáy Vực Challenger Deep</span>
            </div>
            <h2 style="font-size: 2.1rem; margin-bottom: 1rem;">Tầng Hố Thẳm (Hadalpelagic - Rãnh Mariana)</h2>
            <p style="color: #cbd5e1; font-size: 1.05rem;">
                Bạn đã chạm tới điểm sâu nhất Trái Đất: Vực Challenger Deep ở độ sâu 10.984 mét. Áp suất tại đây gấp hơn 1.071 lần mặt biển — tương đương sức nặng của 50 chiếc máy bay phản lực đè lên cơ thể. Bạn đã sẵn sàng thử thách kiến thức đại dương của mình?
            </p>
            <div class="creature-showcase">
                <div class="creature-box">
                    <div style="font-size: 2rem;">🐟</div>
                    <strong>Cá Sên Mariana (Snailfish)</strong>
                    <span style="font-size: 0.85rem; color: #94a3b8;">Kỷ lục loài cá sống sâu nhất (~8.000m) với hộp sọ mềm và nồng độ phân tử TMAO cực cao.</span>
                </div>
                <div class="creature-box">
                    <div style="font-size: 2rem;">🦐</div>
                    <strong>Giáp Xác Amphipod Khổng Lồ</strong>
                    <span style="font-size: 0.85rem; color: #94a3b8;">Phủ lớp gel nhôm hydroxit quanh vỏ để không bị áp suất cực đại làm tan chảy.</span>
                </div>
                <div class="creature-box">
                    <div style="font-size: 2rem;">🦠</div>
                    <strong>Trùng Chân Giả Xenophyophores</strong>
                    <span style="font-size: 0.85rem; color: #94a3b8;">Sinh vật đơn bào khổng lồ rộng tới 20 cm sinh sống ngay dưới đáy Challenger Deep.</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Thanh cố định Đăng nhập / Đăng ký dưới đáy -->
    <div class="fixed-bottom-auth-bar">
        <div style="display: flex; align-items: center; gap: 0.85rem;">
            <span style="font-size: 1.4rem;">🧭</span>
            <div>
                <div style="font-weight: 700; font-size: 0.95rem;">Sẵn sàng chinh phục Đấu Trường Kiến Thức Đại Dương?</div>
                <div style="font-size: 0.8rem; color: var(--text-muted);">Đọc tư liệu khoa học, phản xạ tốc độ kiểu Kahoot và ghi danh lên Bảng Xếp Hạng Toàn Cầu.</div>
            </div>
        </div>

        <div style="display: flex; gap: 0.85rem;">
            @auth
                <a href="{{ route('quiz.play') }}" class="btn btn-primary">🚀 Vào Làm Quiz Ngay</a>
                <a href="{{ route('dashboard') }}" class="btn btn-outline">Bảng Điều Khiển</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline">🔐 Đăng Nhập</a>
                <a href="{{ route('register') }}" class="btn btn-primary">⚡ Đăng Ký Tài Khoản</a>
            @endauth
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function scrollToZone(id) {
        const el = document.getElementById(id);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth' });
        }
    }

    window.addEventListener('scroll', () => {
        const scrollTop = window.scrollY;
        const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
        const scrollFraction = maxScroll > 0 ? Math.min(Math.max(scrollTop / maxScroll, 0), 1) : 0;

        const simulatedMeters = Math.round(scrollFraction * 10984);
        document.getElementById('currentDepthDisplay').textContent = simulatedMeters.toLocaleString() + 'm';

        const sections = document.querySelectorAll('.ocean-zone-section');
        const scaleItems = document.querySelectorAll('.depth-scale-item');
        let activeZoneId = 'zone-0';
        let activeZoneTitle = 'Tầng Ánh Sáng';

        sections.forEach(sec => {
            const rect = sec.getBoundingClientRect();
            if (rect.top <= window.innerHeight * 0.5) {
                activeZoneId = sec.id;
                activeZoneTitle = sec.getAttribute('data-zone');
            }
        });

        document.getElementById('currentZoneName').textContent = activeZoneTitle;

        scaleItems.forEach(item => {
            if (item.getAttribute('data-target') === activeZoneId) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    });
</script>
@endpush
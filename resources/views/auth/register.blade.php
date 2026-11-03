@extends('layouts.app')

@section('title', 'Đăng Ký Tài Khoản Thám Hiểm | AbyssalMind')

@section('content')
<div class="container" style="max-width: 500px; margin-top: 2.5rem;">
    <div class="bento-card">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <div style="font-size: 2.5rem;">🤿</div>
            <h1 style="font-size: 1.65rem;">Đăng Ký Nhà Thám Hiểm</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Lưu trữ điểm số và đua top trên bảng xếp hạng đại dương</p>
        </div>

        @if($errors->any())
            <div style="background: rgba(239,68,68,0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 0.85rem; border-radius: 10px; margin-bottom: 1.2rem; font-size: 0.9rem;">
                <ul style="padding-left: 1.1rem;">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register.submit') }}" method="POST" style="display: flex; flex-direction: column; gap: 1.1rem;">
            @csrf
            <div>
                <label style="display:block; margin-bottom:0.4rem; font-weight:600; font-size:0.9rem;">Họ và Tên</label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control" placeholder="VD: Nguyễn Văn A" required>
            </div>

            <div>
                <label style="display:block; margin-bottom:0.4rem; font-weight:600; font-size:0.9rem;">Địa chỉ Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="diver@ocean.io" required>
            </div>

            <div>
                <label style="display:block; margin-bottom:0.4rem; font-weight:600; font-size:0.9rem;">Mật khẩu (Tối thiểu 6 ký tự)</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div>
                <label style="display:block; margin-bottom:0.4rem; font-weight:600; font-size:0.9rem;">Nhập lại mật khẩu</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
                ⚡ Tạo Tài Khoản Mới
            </button>
        </form>
    </div>
</div>
@endsection
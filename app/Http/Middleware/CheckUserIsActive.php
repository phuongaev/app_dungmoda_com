<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Kiểm tra xem người dùng đã đăng nhập vào guard 'admin' chưa
        // và tài khoản của họ có đang bị tắt (is_active = 0) hay không
        if (Auth::guard('admin')->check() && !Auth::guard('admin')->user()->is_active) {
            
            // Nếu tài khoản bị tắt, thực hiện đăng xuất họ
            Auth::guard('admin')->logout();

            // Xóa session của họ
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            // Chuyển hướng về trang đăng nhập và gửi kèm thông báo lỗi
            return redirect()->route('admin.login')
                             ->withErrors(['username' => 'Tài khoản của bạn đã bị vô hiệu hóa.']);
        }

        // Nếu tài khoản hoạt động bình thường, cho phép request đi tiếp
        return $next($request);
    }
}
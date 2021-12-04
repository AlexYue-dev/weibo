<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Mail;
use Illuminate\Support\Str;
use DB;

class PasswordController extends Controller
{
    public function __construct()
    {
        // 限流 重置页面每分钟最多访问两次
        $this->middleware('throttle:2,1',[
            'only'=> ['showLinkRequestForm'],
        ]);

        // 发送邮件接口每十分钟发送三次
        $this->middleware('throttle:3,10', [
            'only' => ['sendResetLinkEmail']
        ]);
    }

    /**
     * 跳转到发送邮件界面
     * @return Application|Factory|View
     */
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    /**
     * 发送认证邮件
     * @param Request $request
     * @return RedirectResponse
     */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        // 1.验证邮箱
        $request->validate(['email' => 'required|email']);
        $email = $request->email;

        // 2.获取对应的用户
        $user = User::where('email', $email)->first();

        // 3.如果用户不存在
        if (is_null($user)) {
            session()->flash('danger', '邮箱未注册');
            return redirect()->back()->withInput();
        }

        // 4.生成重置密码的token
        $token = hash_hmac('sha256', Str::random(40), config('app.key'));

        // 5.将email & token 写入数据库 保证email的唯一性
        DB::table('password_resets')->updateOrInsert(['email' => $email], [
            'email' => $email,
            'token' => Hash::make($token),
            'created_at' => new Carbon,
        ]);

        // 6. 将 Token 链接发送给用户
        Mail::send('emails.reset_link', compact('token'), function ($message) use ($email) {
            $message->to($email)->subject("忘记密码");
        });

        session()->flash('success', '重置密码邮件发送成功');
        return redirect()->back();
    }

    /**
     * 点击邮件链接，跳转到重置密码界面
     * @param Request $request
     * @return Application|Factory|View
     */
    public function showResetForm(Request $request)
    {
        $token = $request->route()->parameter('token');
        return view('auth.passwords.reset', compact('token'));
    }

    public function reset(Request $request)
    {
        // 1.数据验证
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6'
        ]);

        $email = $request->email;
        $token = $request->token;
        // 找回密码链接的有效时间
        $expires = 60 * 10;

        // 2. 获取对应的用户
        $user = User::where('email', $email)->first();

        // 3.如果找不到用户

        if (is_null($user)) {
            session()->flash('danger', '邮箱未注册');
            return redirect()->back()->withInput();
        }

        // 4.读取重置记录
        $record = (array)DB::table('password_resets')->where('email', $email)->first();

        // 5.记录存在
        if ($record) {
            // 5.1 检查是否过期
            if (Carbon::parse($record['created_at'])->addSecond($expires)->isPast()) {
                session()->flash('danger', '链接已过期，请重新尝试');
                return redirect()->back();
            }
            // 5.2 检查token是否正确
            if (!hash::check($token, $record['token'])) {
                session()->flash('danger', 'token错误');
            }
            // 5.3 一切正常，更新用户密码
            $user->update(['passsword' => bcrypt($request->password)]);
            // 5.4 提示用户更新密码成功
            session()->flash('success', '密码更新成功');
            return redirect()->route('login');
        }

        // 记录不存在
        session()->flash('danger', '未找到重置记录');
        return redirect()->back();
    }
}

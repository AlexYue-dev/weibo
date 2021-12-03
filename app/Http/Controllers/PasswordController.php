<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Mail;
use Illuminate\Support\Str;
use DB;

class PasswordController extends Controller
{
    /**
     * 跳转到发送邮件界面
     * @return Application|Factory|View
     */
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    public function sendResetLinkEmail(Request $request)
    {
        // 1.验证邮箱
        $request->validate(['email'=>'required|email']);
        $email = $request->email;

        // 2.获取对应的用户
        $user = User::where('email',$email)->first();

        // 3.如果用户不存在
        if (is_null($user)){
            session()->flash('danger','邮箱未注册');
            return redirect()->back()->withInput();
        }

        // 4.生成重置密码的token
        $token = hash_hmac('sha256',Str::random(40), config('app.key'));

        // 5.将email & token 写入数据库 保证email的唯一性
        DB::table('password_resets')->updateOrInsert(['email'=>$email],[
            'email' => $email,
            'token' => Hash::make($token),
            'created_at' => new Carbon,
        ]);

        // 6. 将 Token 链接发送给用户
        Mail::send('emails.reset_link', compact('token'), function ($message) use ($email) {
            $message->to($email)->subject("忘记密码");
        });

        session()->flash('success','重置密码邮件发送成功');
        return redirect()->back();
    }
}

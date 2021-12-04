<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SessionsController extends Controller
{
    public function __construct()
    {
        // 非登陆用户只能访问登陆接口
        $this->middleware('guest', [
            'only' => ['create']
        ]);

        // 登陆限流 每十分钟最多登陆十次
        $this->middleware('throttle:100,10', [
            'only' => ['store']
        ]);
    }

    /**
     * 跳转到登陆界面
     * @return Application|Factory|View
     */
    public function create()
    {
        return view('sessions.create');
    }

    /**
     * 用户登陆
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $this->validate($request, [
            'email' => 'required|email|max:255',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials, $request->has('remember'))) {
            if (Auth::user()->activated) {
                session()->flash('success', '欢迎回来');
                $fallback = route('users.show', Auth::user());
                return redirect()->intended($fallback);
            } else {
                Auth::logout();
                session()->flash('warning', '您的账号尚未激活，请检查邮箱中的邮件进行激活');
                return redirect('/');
            }
        } else {
            session()->flash('danger', '很抱歉，邮箱和密码不匹配');
            return redirect()->back()->withInput();
        }
    }

    /**
     * 退出登陆
     * @return Application|RedirectResponse|Redirector
     */
    public function destroy()
    {
        Auth::logout();
        session()->flash('success', '您已成功推出');
        return redirect('login');
    }
}

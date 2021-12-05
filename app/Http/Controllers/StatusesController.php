<?php

namespace App\Http\Controllers;

use App\Models\Status;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StatusesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $this->validate($request,[
            'content'=>'required|max:140'
        ]);

        Auth::user()->statuses()->create([
            'content' => $request['content'],
            ]);

        session()->flash('success','发布成功');
        return redirect()->back();
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Status $status): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('destroy',$status);
        $status->delete();
        session()->flash('success','删除成功');
        return redirect()->back();

    }
}

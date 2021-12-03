@extends('layouts.default')

@section('content')
    <div class="jumbotron">
        <h1>Hi guys</h1>
        <p class="lead">
            What you see now is <a href="https://alexyue-dev.herokuapp.com/">AlexYue Blog</a>
        </p>
        <p>
            Everything will start from here.
        </p>
        <p>
            <a class="btn btn-lg btn-success" href="{{ route('signup') }}" role="button">Register</a>
        </p>
    </div>
@stop

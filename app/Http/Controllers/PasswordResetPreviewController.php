<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PasswordResetPreviewController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = User::first();

        $token = app('auth.password.broker')->createToken($user);

        $notification = new ResetPassword($token);
        $notification->id = Str::uuid()->toString();

        $mail = $notification->toMail($user);

        return response($mail->render())->header('Content-Type', 'text/html');
    }
}

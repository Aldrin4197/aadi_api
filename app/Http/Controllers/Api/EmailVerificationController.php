<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(['emailVerified'=>$request->user()->hasVerifiedEmail()]);
    }
    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) return response()->json(['emailVerified'=>true]);
        try { $request->user()->sendEmailVerificationNotification(); }
        catch (\Throwable $exception) { return response()->json(['message'=>'The verification email could not be sent. Please try again shortly.'], 503); }
        return response()->json(['message'=>'Verification email sent.', 'emailVerified'=>false]);
    }
}

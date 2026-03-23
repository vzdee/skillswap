<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('password.request')
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }

        if (! User::where('email', $request->email)->exists()) {
            return redirect()
                ->route('password.request')
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('passwords.user')]);
        }

        DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))
            ->where('email', $request->email)
            ->delete();

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status == Password::RESET_LINK_SENT
                    ? redirect()->route('password.request')->with('status', __($status))
                    : redirect()->route('password.request')->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    /**
     * Authenticate user session for SPA.
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        if (! Auth::guard('web')->attempt($data)) {
            return response()->json(['message' => 'Kredensial tidak valid.'], 401);
        }

        $request->session()->regenerate();
        $user = Auth::guard('web')->user();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
        ])->header('Cache-Control', 'no-store');
    }

    /**
     * Terminate the authenticated user session.
     */
    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}

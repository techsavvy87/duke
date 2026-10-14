<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\web\KennelController;
use App\Http\Controllers\web\RoomController;

class EnsureDemoAccountAccess
{
    /**
     * Controllers a demo user may only use while Boarding is enabled for the demo account.
     */
    private const BOARDING_CONTROLLERS = [
        KennelController::class,
        RoomController::class,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isDemo()) {
            return $next($request);
        }

        if ($user->demoAccessBlocked()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'This demo account is inactive.',
                ], 403);
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with([
                'status' => 'fail',
                'message' => 'This demo account is inactive.',
            ]);
        }

        $controller = $request->route()?->getControllerClass();

        $denied = in_array($controller, self::BOARDING_CONTROLLERS, true) && !$user->demoHasServiceOf('isBoardingService');

        if ($denied) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => 'This feature is not enabled for your demo account.',
                ], 403);
            }

            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}

<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserHasPermission;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => EnsureUserHasPermission::class,
            'role' => EnsureUserHasRole::class,
        ]);

        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $exception, Request $request) {
            if (
                $request->expectsJson()
                || $exception instanceof AuthenticationException
                || $exception instanceof ValidationException
            ) {
                return null;
            }

            $status = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 500;

            $message = $exception instanceof HttpExceptionInterface
                ? ($exception->getMessage() ?: match ($status) {
                    403 => 'Anda tidak memiliki akses ke halaman ini.',
                    404 => 'Halaman yang Anda cari tidak ditemukan.',
                    default => 'Terjadi kesalahan pada aplikasi.',
                })
                : ($exception->getMessage() ?: 'Terjadi kesalahan pada aplikasi.');

            return response()->view('errors.app', [
                'status' => $status,
                'message' => $message,
            ], $status);
        });
    })->create();

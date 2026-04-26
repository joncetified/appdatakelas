<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserHasPermission;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
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

            $defaultMessage = match ($status) {
                403 => 'Anda tidak memiliki akses ke halaman ini.',
                404 => 'Halaman yang Anda cari tidak ditemukan.',
                default => 'Terjadi kesalahan pada aplikasi.',
            };

            $message = match (true) {
                $status === 403, $status === 404 => $defaultMessage,
                $exception instanceof HttpExceptionInterface => $exception->getMessage() ?: $defaultMessage,
                default => $defaultMessage,
            };

            $brandName = 'SPH';
            $brandLogoPath = 'site/permata-harapan-logo.svg';
            $homeUrl = $request->user() ? route('dashboard') : route('login');

            if (! app()->bound('view')) {
                $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
                $safeStatus = htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8');
                $safeBrandName = htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8');
                $safeHomeUrl = htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8');

                return new SymfonyResponse(<<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error {$safeStatus} - {$safeBrandName}</title>
</head>
<body style="margin:0;font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f8fafc;color:#0f172a;">
    <main style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;">
        <section style="max-width:720px;width:100%;background:#fff;border:1px solid #e2e8f0;border-radius:24px;padding:32px;box-shadow:0 20px 40px rgba(15,23,42,.08);text-align:center;">
            <p style="margin:0 0 12px;color:#d97706;font-size:12px;font-weight:700;letter-spacing:.3em;text-transform:uppercase;">Error {$safeStatus}</p>
            <h1 style="margin:0 0 16px;font-size:36px;line-height:1.1;">Terjadi masalah pada halaman</h1>
            <p style="margin:0 0 24px;font-size:16px;line-height:1.7;color:#475569;">{$safeMessage}</p>
            <a href="{$safeHomeUrl}" style="display:inline-block;padding:12px 20px;border-radius:999px;background:#0f172a;color:#fff;text-decoration:none;font-weight:600;">Kembali ke sistem</a>
        </section>
    </main>
</body>
</html>
HTML, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
            }

            return new SymfonyResponse(view('errors.app', [
                'status' => $status,
                'message' => $message,
                'brandName' => $brandName,
                'brandLogoPath' => $brandLogoPath,
                'homeUrl' => $homeUrl,
            ])->render(), $status);
        });
    })->create();

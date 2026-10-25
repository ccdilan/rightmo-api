<?php

use App\Exceptions\CategoryInUse;
use App\Exceptions\DuplicateCategoryName;
use App\Exceptions\DuplicateProductName;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', api: __DIR__.'/../routes/api.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {})
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request): bool => $request->is('api/*') || $request->expectsJson());
        $exceptions->dontReport([DuplicateProductName::class, DuplicateCategoryName::class, CategoryInUse::class]);
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }
            if ($exception instanceof DuplicateCategoryName) {
                return response()->json(['message' => 'A category with this name already exists.', 'errors' => ['name' => ['A category with this name already exists.']]], 422);
            }
            if ($exception instanceof CategoryInUse) {
                return response()->json(['message' => 'This category contains products. Move or delete those products before deleting the category.'], 409);
            }
            if ($exception instanceof DuplicateProductName) {
                return response()->json(['message' => 'A product with this name already exists.', 'errors' => ['name' => ['A product with this name already exists.']]], 422);
            }
            if ($exception instanceof ValidationException) {
                return response()->json(['message' => $exception->getMessage(), 'errors' => $exception->errors()], 422);
            }
            if ($exception instanceof AuthenticationException) {
                return response()->json(['message' => $exception->getMessage()], 401);
            }
            if ($exception instanceof ModelNotFoundException) {
                return response()->json(['message' => 'Resource not found.'], 404);
            }
            if ($exception instanceof PostTooLargeException) {
                return response()->json(['message' => 'The upload is too large.'], 413);
            }
            if ($exception instanceof HttpExceptionInterface) {
                $status = $exception->getStatusCode();
                $message = match ($status) {
                    401 => 'Unauthenticated.',403 => 'Forbidden.',404 => 'Resource not found.',405 => 'Method not allowed.',413 => 'The upload is too large.',429 => 'Too many requests. Please try again shortly.',default => 'The request could not be completed.'
                };

                return response()->json(['message' => $message], $status, $exception->getHeaders());
            }

            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        });
    })->create();

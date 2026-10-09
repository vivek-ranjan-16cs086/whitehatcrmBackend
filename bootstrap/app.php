<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
//
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
//
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
	->withCommands([
        App\Console\Commands\LeadReminder::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
		
		//
		 $middleware->append(
        \App\Http\Middleware\ForceJsonResponse::class
    );
	//
		 $middleware->append(
			  \App\Http\Middleware\MaintenanceMode::class
		 );
        $middleware->alias([
            'super-admin' => \App\Http\Middleware\SuperAdminMiddleware::class,
            'api-lead-view' => \App\Http\Middleware\EnsureApiLeadViewPermission::class,
			'no-cache'    => \App\Http\Middleware\NoCacheHeaders::class,
            'role'        => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'  => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
	//
		// 401 - Unauthenticated API request
        $exceptions->render(function (
            AuthenticationException $e,
            Request $request
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Authentication required.',
                    'error' => 'UNAUTHENTICATED',
                ], 401);
            }
        });
 // 403 - Authenticated but not allowed
$exceptions->render(function (
    HttpException $e,
    Request $request
) {
    if (
        $request->is('api/*') &&
        $e->getStatusCode() === 403
    ) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage() ?: 'Access denied.',
            'error' => 'FORBIDDEN',
        ], 403);
    }
});

        // 404 - Wrong API route
        $exceptions->render(function (
            NotFoundHttpException $e,
            Request $request
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'API route not found.',
                    'error' => 'NOT_FOUND',
                ], 404);
            }
        });
		//
    })->create();
	
	

<?php

use App\Http\Middleware\EitherAuthOrOnboarded;
use App\Http\Middleware\EnsureApplicantOnboarded;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Database\QueryException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\Http\Middleware\AuthApplicantMiddleware;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: require __DIR__ . '/../routes/routes.php'
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->alias([
            'applicant.onboarded' => EnsureApplicantOnboarded::class,
            'either.auth.or.onboarded' => EitherAuthOrOnboarded::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        
        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            return errorResponse( "Record not found", $exception, 404);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            return errorResponse( "This action is unauthorized", $exception, 403);
        });

        $exceptions->render(function (AccessDeniedHttpException $exception, Request $request) {
            return errorResponse( "This action is unauthorized", $exception, 403);
        });

        $exceptions->render(function (QueryException $exception, Request $request) {
            return errorResponse("An error occurred while retrieving data. Please try again later.", $exception, 500);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            return errorResponse("An error occurred while retrieving data. Please try again later.", $exception, 401);
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            return validationResponse("Information is not valid.", $exception, statusCode: 422);
        });

        $exceptions->render(function (Exception $exception, Request $request) {

            if($exception->getMessage() == 'Route [login] not defined.'){
                return errorResponse("Something went wrong", $exception, 401);
            }
            
            return errorResponse("Something went wrong", $exception, 500);
        });

    })->create();

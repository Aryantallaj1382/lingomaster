<?php

use App\Http\Middleware\CheckAllowedDomains;
use App\Http\Middleware\EnsureProfessorIsApproved;
use App\Http\Middleware\EnsureUserIsSeller;
use App\Http\Middleware\JsonMiddleware;
use App\Http\Middleware\OptionalAuthenticate;
use App\Http\Middleware\RestrictApiDomain;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // اضافه کردن middleware استاد تایید شده به گروه API

        $middleware->appendToGroup('api', [JsonMiddleware::class]);
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);
//        $middleware->appendToGroup('api', RestrictApiDomain::class);
//        $middleware->appendToGroup('api', CheckAllowedDomains::class);

        $middleware->alias([
            'optional.auth' => OptionalAuthenticate::class,
            'professor.approved' => EnsureProfessorIsApproved::class,
            'wants_json' => JsonMiddleware::class,
//            'restrict.api.domains' => \App\Http\Middleware\RestrictApiDomain::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();




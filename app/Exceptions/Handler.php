<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{

	public function render($request, Throwable $exception)
	{
		if ($request->is('api/*') && $exception instanceof NotFoundHttpException) {
			return response()->json([
				'error' => 'Bad route called',
				'status'=> false
			], 404);
		}

		return parent::render($request, $exception);
	}
}
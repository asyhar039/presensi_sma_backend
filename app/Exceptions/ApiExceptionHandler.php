<?php

namespace App\Exceptions;

use App\Traits\ApiResponseTrait;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ApiExceptionHandler
{
    use ApiResponseTrait;

    /**
     * Render the given exception as a JSON response when appropriate.
     */
    public function renderApiException(Throwable $e, Request $request): ?JsonResponse
    {
        if ($e instanceof ValidationException) {
            return $this->errorResponse(
                message: 'Validation failed.',
                code: Response::HTTP_UNPROCESSABLE_ENTITY,
                errors: $e->errors(),
            );
        }

        if ($e instanceof AuthorizationException) {
            return $this->errorResponse(
                message: $e->getMessage() ?: 'Forbidden.',
                code: Response::HTTP_FORBIDDEN,
            );
        }

        if ($e instanceof NotFoundHttpException) {
            return $this->errorResponse(message: 'Page not found.', code: Response::HTTP_NOT_FOUND);
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return $this->errorResponse(message: 'Method not allowed.', code: Response::HTTP_METHOD_NOT_ALLOWED);
        }

        if ($e instanceof HttpException) {
            return $this->renderHttpException($e);
        }

        return $this->renderFallbackException($e);
    }

    /**
     * Render an HttpException with its native status code.
     */
    protected function renderHttpException(HttpException $e): JsonResponse
    {
        $status = $e->getStatusCode();
        $message = $e->getMessage();

        if ($status === Response::HTTP_FORBIDDEN) {
            return $this->errorResponse(message: $message ?: 'Forbidden.', code: Response::HTTP_FORBIDDEN);
        }

        if ($status === Response::HTTP_UNAUTHORIZED) {
            return $this->errorResponse(message: $message ?: 'Unauthenticated.', code: Response::HTTP_UNAUTHORIZED);
        }

        if ($status === Response::HTTP_UNPROCESSABLE_ENTITY) {
            return $this->errorResponse(message: $message ?: 'Unprocessable entity.', code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->errorResponse(
            message: $message ?: 'Http error.',
            code: $status,
        );
    }

    /**
     * Render an unexpected exception; hide details in production.
     */
    protected function renderFallbackException(Throwable $e): ?JsonResponse
    {
        if (app()->environment('production')) {
            report($e);

            return $this->errorResponse(
                message: 'Internal server error.',
                code: Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }

        return null;
    }
}

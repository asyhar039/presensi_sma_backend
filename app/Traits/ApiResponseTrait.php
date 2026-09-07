<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides consistent JSON envelopes for success, error and paginated responses.
 */
trait ApiResponseTrait
{
    /**
     * Build a JSON response envelope.
     *
     * @param  mixed  $data  Payload to return.
     * @param  string  $message  Human-readable message.
     * @param  int  $code  HTTP status code.
     * @param  mixed|null  $meta  Optional pagination or auxiliary metadata.
     */
    protected function response(
        mixed $data = [],
        string $message = 'Success',
        int $code = Response::HTTP_OK,
        mixed $meta = null
    ): JsonResponse {
        $response = [
            'message' => $message,
            'data' => $data,
        ];

        if ($meta !== null) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $code);
    }

    /**
     * Build a successful JSON response.
     *
     * @param  mixed  $data  Payload to return.
     * @param  string  $message  Human-readable message.
     * @param  int  $code  HTTP status code.
     * @param  mixed|null  $meta  Optional pagination or auxiliary metadata.
     */
    public function successResponse(
        mixed $data = [],
        string $message = 'Success',
        int $code = Response::HTTP_OK,
        mixed $meta = null
    ): JsonResponse {
        return $this->response(
            data: $data,
            message: $message,
            code: $code,
            meta: $meta
        );
    }

    /**
     * Build an error JSON response.
     *
     * @param  string  $message  Error message.
     * @param  int  $code  HTTP status code.
     * @param  mixed|null  $errors  Validation or detail errors.
     */
    public function errorResponse(
        string $message = 'Error encountered',
        int $code = Response::HTTP_BAD_REQUEST,
        mixed $errors = null
    ): JsonResponse {
        return $this->response(
            data: $errors,
            message: $message,
            code: $code
        );
    }

    /**
     * Build pagination metadata from a paginator.
     *
     * Centralizes the meta shape so controllers remain DRY:
     * meta: [current_page, per_page, total, last_page]
     *
     * @return array{current_page: int, per_page: int, total: int, last_page: int}
     */
    public function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    /**
     * Build a paginated success response reusing paginationMeta().
     *
     * @param  LengthAwarePaginator<int, mixed>  $paginator  Paginator carrying zero-filled report rows.
     * @param  string  $message  Success message.
     * @param  int  $code  HTTP status code.
     */
    public function paginatedResponse(
        LengthAwarePaginator $paginator,
        string $message = 'Success',
        int $code = Response::HTTP_OK
    ): JsonResponse {
        return $this->successResponse(
            data: $paginator->items(),
            message: $message,
            code: $code,
            meta: $this->paginationMeta($paginator),
        );
    }
}

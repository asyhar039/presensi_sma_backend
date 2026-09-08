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
        mixed $data = null,
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
        mixed $data = null,
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
     * meta: [page, per_page, total, total_pages]
     *
     * @return array{page: int, per_page: int, total: int, total_pages: int}
     */
    public function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'total_pages' => $paginator->lastPage(),
        ];
    }

    /**
     * Build a paginated success response reusing paginationMeta().
     *
     * Pass the already-transformed payload (e.g. `RoomResource::collection($paginator->items())`)
     * so Eloquent Resources stay in charge of presentation while meta stays consistent:
     * meta: [page, per_page, total, total_pages]
     *
     * @param  LengthAwarePaginator<int, mixed>  $paginator  Paginator the meta is derived from.
     * @param  mixed  $data  Transformed page items, typically a resource collection.
     * @param  string  $message  Success message.
     * @param  int  $code  HTTP status code.
     */
    public function paginatedResponse(
        LengthAwarePaginator $paginator,
        mixed $data,
        string $message = 'Success',
        int $code = Response::HTTP_OK
    ): JsonResponse {
        return $this->successResponse(
            data: $data,
            message: $message,
            code: $code,
            meta: $this->paginationMeta($paginator),
        );
    }
}

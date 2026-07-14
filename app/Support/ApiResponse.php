<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Standardized JSON envelope for every API response so clients can rely on a
 * single, predictable shape: { success, message, data, errors, pagination,
 * meta, status }.
 */
class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'Success', int $code = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
            'pagination' => null,
            'meta' => $meta ?: null,
            'status' => $code,
        ], $code);
    }

    public static function error(string $message = 'Something went wrong', mixed $errors = null, int $code = 400, array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
            'pagination' => null,
            'meta' => $meta ?: null,
            'status' => $code,
        ], $code);
    }

    public static function paginated(LengthAwarePaginator $paginator, string $message = 'Success', ?string $resourceClass = null): JsonResponse
    {
        $items = $resourceClass ? $resourceClass::collection($paginator->items()) : $paginator->items();

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $items,
            'errors' => null,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'meta' => null,
            'status' => 200,
        ]);
    }
}

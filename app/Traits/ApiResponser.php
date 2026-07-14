<?php

namespace App\Traits;

use App\Support\ApiResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Convenience wrappers around App\Support\ApiResponse so API controllers can
 * call $this->success(...) / $this->error(...) directly.
 */
trait ApiResponser
{
    protected function success(mixed $data = null, string $message = 'Success', int $code = 200, array $meta = []): JsonResponse
    {
        return ApiResponse::success($data, $message, $code, $meta);
    }

    protected function error(string $message = 'Something went wrong', mixed $errors = null, int $code = 400, array $meta = []): JsonResponse
    {
        return ApiResponse::error($message, $errors, $code, $meta);
    }

    protected function paginated(LengthAwarePaginator $paginator, string $message = 'Success', ?string $resourceClass = null): JsonResponse
    {
        return ApiResponse::paginated($paginator, $message, $resourceClass);
    }
}

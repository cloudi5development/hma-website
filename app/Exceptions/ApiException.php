<?php

namespace App\Exceptions;

use Exception;

/**
 * Throw this anywhere in the API layer to short-circuit with a clean,
 * client-facing error. bootstrap/app.php renders it through ApiResponse using
 * the message, errors payload and status code supplied here.
 *
 *   throw new ApiException('Invalid coupon.', ['code' => 'expired'], 422);
 */
class ApiException extends Exception
{
    protected mixed $errors;

    protected int $statusCode;

    public function __construct(string $message = 'Something went wrong', mixed $errors = null, int $statusCode = 400)
    {
        parent::__construct($message);

        $this->errors = $errors;
        $this->statusCode = $statusCode;
    }

    public function getErrors(): mixed
    {
        return $this->errors;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}

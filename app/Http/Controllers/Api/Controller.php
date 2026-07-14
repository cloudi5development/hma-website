<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Base controller for all API endpoints. Extend this (not the root Controller)
 * for anything under App\Http\Controllers\Api so you get the $this->success() /
 * $this->error() / $this->paginated() helpers and authorization support.
 */
abstract class Controller extends BaseController
{
    use ApiResponser, AuthorizesRequests;
}

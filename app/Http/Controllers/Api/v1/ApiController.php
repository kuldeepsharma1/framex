<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Exceptions\HttpResponseException;

class ApiController extends Controller
{
    /**
     * Authorize a specific scope for the token.
     */
    protected function authorizeScope(string $scope): void
    {
        if (!request()->user()->tokenCan($scope)) {
            throw new HttpResponseException(
                response()->json([
                    'message' => "Forbidden. Token lacks access to scope '{$scope}'."
                ], 403)
            );
        }
    }
}

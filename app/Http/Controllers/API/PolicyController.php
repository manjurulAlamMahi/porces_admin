<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Policy;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PolicyController extends Controller
{
    use ApiResponse;

    // getBeachPolicy

    public function getBeachPolicy()
    {
        $policy = Policy::where('type', 'beach')->first();

        if (!$policy) {
            return $this->error(null, 'Beach policy not found', 404);
        }

        $policy = [
            'id'      => $policy->id,
            'type'    => $policy->type,
            'content' => $policy->content,
        ];

        return $this->success($policy, 'Beach policy retrieved successfully');
    }

    // getDisclaimersPolicy

    public function getDisclaimersPolicy()
    {
        $policy = Policy::where('type', 'disclaimers')->first();

        if (!$policy) {
            return $this->error(null, 'Disclaimers policy not found', 404);
        }

        $policy = [
            'id'      => $policy->id,
            'type'    => $policy->type,
            'content' => $policy->content,
        ];

        return $this->success($policy, 'Disclaimers policy retrieved successfully');
    }
}

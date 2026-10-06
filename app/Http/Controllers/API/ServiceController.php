<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    use ApiResponse;
    // getServices
    public function getServices(Request $request)
    {
        // Fetch all services
        $services = Service::latest()->get();

        $services = $services->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'icon' => asset($service->icon),
            ];
        });

        return $this->success($services , 'Services fetched successfully.' , 200);

    }
}

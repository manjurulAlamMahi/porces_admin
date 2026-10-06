<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Beach;
use App\Models\BookingChair;
use App\Models\Chair;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BeachController extends Controller
{
    use ApiResponse;
    // index
    public function index()
    {
        $beaches = Beach::where('status', 'active')->get();

        $beaches = $beaches->map(function ($beach) {
            return [
                'id' => $beach->id,
                'name' => $beach->name,
                'image' => asset($beach->image),
            ];
        });

        return $this->success($beaches, 'Beaches retrieved successfully', 200);
    }

    public function getBeachChairs(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'beach_id'      => 'required|exists:beaches,id',
            'date'          => 'nullable|date_format:Y-m-d',
            'column_letter' => 'nullable|string',
            'type'          => 'nullable|in:adult,family',
        ]);

        if ($validated->fails()) {
            return $this->error(null, $validated->errors()->first(), 200);
        }

        $chairsQuery = Chair::where('beach_id', $request->beach_id)
            ->where('status', '!=', 'maintenance')
            ->when(
                $request->column_letter,
                fn($q) =>
                $q->where('column_letter', $request->column_letter)
            )
            ->when(
                $request->type,
                fn($q) =>
                $q->where('type', $request->type)
            );

        $chairs = $chairsQuery->get();

        // Date-based availability check
        if ($request->filled('date')) {
            $date = $request->date;

            $bookedChairIds = BookingChair::whereHas('booking', function ($q) use ($date) {
                $q->whereDate('booking_date', $date)
                    ->whereIn('status', ['pending', 'confirmed', 'checked_in']);
            })->pluck('chair_id')->toArray();

            $chairs->transform(function ($chair) use ($bookedChairIds) {
                $chair->computed_status = in_array($chair->id, $bookedChairIds)
                    ? 'reserved'
                    : 'available';

                return $chair;
            });
        } else {
            $chairs->transform(function ($chair) {
                $chair->computed_status = 'available';
                return $chair;
            });
        }

        return $this->success(
            $chairs->map(fn($chair) => [
                'id'            => $chair->id,
                'code'          => $chair->code,
                'type'          => $chair->type,
                'price'         => $chair->price,
                'status'        => $chair->computed_status,
                'column_letter' => $chair->column_letter,
                'row_number'    => str_pad($chair->row_number, 2, '0', STR_PAD_LEFT),
            ]),
            'Chairs retrieved successfully',
            200
        );
    }


    public function getBeachServices(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'beach_id' => 'required|exists:beaches,id',
        ]);

        if ($validated->fails()) {
            return $this->error(null, $validated->errors()->first(), 200);
        }

        $beach = Beach::where('id', $request->beach_id)
            ->with(['beachServices' => function ($q) {
                $q->where('is_active', true);
            }])
            ->first();

        if (!$beach || $beach->beachServices->isEmpty()) {
            return $this->error(null, 'No active services found for the specified beach', 200);
        }

        $services = $beach->beachServices->map(function ($service) {
            return [
                'id'    => $service->id,
                'name'  => $service->name,
                'price' => $service->price,
                'image' => asset($service->image),
            ];
        });

        return $this->success($services, 'Beach services retrieved successfully', 200);
    }
}

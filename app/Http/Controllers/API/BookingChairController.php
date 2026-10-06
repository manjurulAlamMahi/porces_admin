<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BeachService;
use App\Models\Booking;
use App\Models\BookingChair;
use App\Models\BookingService;
use App\Models\Chair;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BookingChairController extends Controller
{
    use ApiResponse;
    // Book Chair
    public function bookChair(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'beach_id'            => 'required|exists:beaches,id',
            'booking_date'        => 'required|date|after_or_equal:today',
            'chairs'              => 'required|array|min:1',
            'chairs.*'            => 'exists:chairs,id',
            'services'            => 'nullable|array',
            'services.*.id'       => 'required|exists:beach_services,id',
            'services.*.qty'      => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->error(null, $validator->errors()->first(), 200);
        }

        $user = Auth::guard('api')->user();

        DB::beginTransaction();

        try {

            // 1️⃣ Check chair availability
            $alreadyBooked = BookingChair::whereIn('chair_id', $request->chairs)
                ->whereHas('booking', function ($q) use ($request) {
                    $q->where('booking_date', $request->booking_date);
                })
                ->exists();

            if ($alreadyBooked) {
                return $this->error(null, 'Some chairs are already booked for this date', 200);
            }

            // 2️⃣ Get chairs
            $chairs = Chair::whereIn('id', $request->chairs)->get();
            $chairTotal = $chairs->sum('price');

            // 3️⃣ Calculate service total with quantity
            $serviceTotal = 0;

            foreach ($request->services ?? [] as $srv) {
                $service = BeachService::find($srv['id']);
                $serviceTotal += $service->price * $srv['qty'];
            }

            $subtotal = $chairTotal + $serviceTotal;
            $total = $subtotal;

            // 4️⃣ Create booking
            $booking = Booking::create([
                'user_id'      => $user->id,
                'beach_id'     => $request->beach_id,
                'booking_date' => $request->booking_date,
                'subtotal'     => $subtotal,
                'total'        => $total,
                'status'       => 'pending',
            ]);

            // 5️⃣ Attach chairs
            foreach ($chairs as $chair) {
                BookingChair::create([
                    'booking_id' => $booking->id,
                    'chair_id'   => $chair->id,
                    'price'      => $chair->price,
                ]);

                $chair->update(['status' => 'reserved']);
            }

            // 6️⃣ Attach services with qty
            foreach ($request->services ?? [] as $srv) {
                $service = BeachService::find($srv['id']);

                BookingService::create([
                    'booking_id'       => $booking->id,
                    'beach_service_id' => $service->id,
                    'price'            => $service->price,
                    'qty'              => $srv['qty'],
                ]);
            }

            DB::commit();

            return $this->success([
                'booking_id' => $booking->id,
                'total'      => $booking->total,
            ], 'Booking created successfully', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(null, $e->getMessage(), 200);
        }
    }

    // Get Bookings
    public function getBookings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:pending,confirmed,checked_in,completed,cancelled', // 'pending', 'confirmed', 'checked_in', 'completed', 'cancelled'
        ]);

        if ($validator->fails()) {
            return $this->error(null, $validator->errors()->first(), 200);
        }

        $user = Auth::guard('api')->user();

        if(!$user) {
            return $this->error('User not found Or Invalid Token', 404);
        }

        $bookings = Booking::where('user_id', $user->id)->latest();

        if ($request->has('status')) {
            $bookings->where('status', $request->status);
        }

        $bookings = $bookings->get();

        $bookings = $bookings->map(function ($booking) {
            return [
                'id'           => $booking->id,
                'beach_id'     => $booking->beach_id,
                'beach'        => $booking->beach->name,
                'booking_date' => $booking->booking_date,
                'location'     => $booking->beach->location,
                'latitude'     => $booking->beach->latitude == null ? rand(0, 100) : (float) $booking->beach->latitude,
                'longitude'    => $booking->beach->longitude == null ? rand(0, 100) : (float) $booking->beach->longitude,
                'checkedIn'    => $booking->status === 'checked_in' ? true : false,
                'status'       => $booking->status,
            ];
        });

        return $this->success($bookings, 'Bookings', 200);
    }

    // Check In Booking
    public function checkInBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,id',
        ]);

        if ($validator->fails()) {
            return $this->error(null, $validator->errors()->first(), 200);
        }

        $booking = Booking::find($request->booking_id);

        $booking->status = 'checked_in';
        $booking->save();

        return $this->success(null, 'Booking checked in successfully', 200);
    }

    public function cancelBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,id',
        ]);

        if ($validator->fails()) {
            return $this->error(null, $validator->errors()->first(), 200);
        }

        $booking = Booking::find($request->booking_id);

        if ($booking->status === 'cancelled') {
            return $this->error(null, 'Booking is already cancelled', 200);
        }

        if($booking->status === 'completed' || $booking->status === 'checked_in' || $booking->status === 'confirmed') {
            return $this->error(null, 'Completed and checked in bookings cannot be cancelled', 200);
        }

        $booking->status = 'cancelled';
        $booking->save();

        // Release the chairs
        foreach ($booking->chairs as $bookingChair) {
            $chair = Chair::find($bookingChair->chair_id);
            $chair->status = 'available';
            $chair->save();
        }

        return $this->success(null, 'Booking cancelled successfully', 200);
    }

    public function bookingReceipt($booking_id)
    {
        $booking = Booking::find($booking_id);

        if (!$booking) {
            return $this->error(null, 'Booking not found', 404);
        }

        $bookingDetails = [
            'id'           => $booking->id,
            'beach'        => $booking->beach->name,
            'booking_date' => $booking->booking_date,
            'status'       => $booking->status,
            'subtotal'     => $booking->subtotal,
            'total'        => $booking->total,
            'chairs'       => $booking->chairs->map(function ($bc) {
                return [
                    'code'  => $bc->chair->code,
                    'type'  => $bc->chair->type,
                    'price' => $bc->price,
                ];
            }),
            'services'     => $booking->services->map(function ($bs) {
                return [
                    'service_name' => $bs->service->name,
                    'price'        => $bs->price,
                    'qty'          => $bs->qty,
                ];
            }),
        ];

        return $this->success($bookingDetails, 'Booking details', 200);

    }
}

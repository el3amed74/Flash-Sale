<?php

namespace App\Http\Controllers\Api;

use App\DTOs\CreateOrderDTO;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Exceptions\HoldExpiredException;
use App\Http\Requests\CreateOrderRequest;
use App\Exceptions\HoldAlreadyUsedException;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Contracts\OrderServiceInterface;

class OrderController extends Controller
{
    public function __construct(
        private OrderServiceInterface $orderService
    ) {
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        try {
            
            $dto= $request->toDTO();
            $order = $this->orderService->createOrder($dto);

            return response()->json([
                'order_id' => $order->id,
                'hold_id' => $order->hold_id,
                'product_id' => $order->product_id,
                'qty' => $order->qty,
                'total_price' => number_format($order->total_price, 2, '.', ''),
                'status' => $order->status,
            ], Response::HTTP_CREATED);

        } catch (HoldExpiredException|HoldAlreadyUsedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create order',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}


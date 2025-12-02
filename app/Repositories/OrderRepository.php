<?php

namespace App\Repositories;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private readonly Order $model
    )
    {}

    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = $this->model->create($data);

            Log::info('Order created', [
                'order_id' => $order->id,
                'hold_id' => $order->hold_id,
                'product_id' => $order->product_id,
                'qty' => $order->qty,
                'total_price' => $order->total_price,
                'status' => $order->status,
            ]);

            return $order;
        });
    }

    public function findById(int $id): ?Order
    {
        return $this->model->find($id);
    }

    public function findByHoldId(int $holdId): ?Order
    {
        return $this->model->where('hold_id', $holdId)->first();
    }

    public function updateStatus(int $orderId, string $status, ?string $paymentReference = null): bool
    {
        return DB::transaction(function () use ($orderId, $status, $paymentReference) {
            $order = $this->model->where('id', $orderId)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                return false;
            }

            $updateData = ['status' => $status];

            if ($paymentReference !== null) {
                $updateData['payment_reference'] = $paymentReference;
            }

            $order->update($updateData);

            Log::info('Order status updated', [
                'order_id' => $orderId,
                'old_status' => $order->getOriginal('status'),
                'new_status' => $status,
                'payment_reference' => $paymentReference,
            ]);

            return true;
        });
    }
}


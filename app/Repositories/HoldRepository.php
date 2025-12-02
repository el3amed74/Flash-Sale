<?php

namespace App\Repositories;

use App\Models\Hold;
use App\Repositories\Contracts\HoldRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HoldRepository implements HoldRepositoryInterface
{
    public function __construct(
        private readonly Hold $model
    )
    {}
    
    public function create(array $data): Hold
    {
        return DB::transaction(function () use ($data) {
            $hold = $this->model->create($data);

            Log::info('Hold created', [
                'hold_id' => $hold->id,
                'product_id' => $hold->product_id,
                'qty' => $hold->qty,
                'expires_at' => $hold->expires_at,
            ]);

            return $hold;
        });
    }

    public function findById(int $id): ?Hold
    {
        return $this->model->find($id);
    }

    public function findByIdempotencyKey(string $idempotencyKey): ?Hold
    {
        return $this->model->where('idempotency_key', $idempotencyKey)->first();
    }

    public function findByProductIdAndStatus(int $productId, string $status): Collection
    {
        return $this->model->where('product_id', $productId)
            ->where('status', $status)
            ->get();
    }

    public function findExpiredHolds(int $limit = 100): Collection
    {
        return $this->model->where('status', Hold::STATUS_ACTIVE)
            ->where('expires_at', '<=', now())
            ->limit($limit)
            ->get();
    }

    public function markAsUsed(int $holdId): bool
    {
        return DB::transaction(function () use ($holdId) {
            $hold = $this->model->where('id', $holdId)
                ->where('status', Hold::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            if (! $hold) {
                return false;
            }

            $hold->update([
                'status' => Hold::STATUS_USED,
                'used_at' => now(),
            ]);

            Log::info('Hold marked as used', [
                'hold_id' => $holdId,
            ]);

            return true;
        });
    }

    public function markAsExpired(int $holdId): bool
    {
        return DB::transaction(function () use ($holdId) {
            $hold = $this->model->where('id', $holdId)
                ->where('status', Hold::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            if (! $hold) {
                return false;
            }

            $hold->update([
                'status' => Hold::STATUS_EXPIRED,
            ]);

            Log::info('Hold marked as expired', [
                'hold_id' => $holdId,
            ]);

            return true;
        });
    }

    public function releaseHold(int $holdId): bool
    {
        return DB::transaction(function () use ($holdId) {
            $hold = $this->model->where('id', $holdId)
                ->lockForUpdate()
                ->first();

            if (! $hold || $hold->status !== Hold::STATUS_ACTIVE) {
                return false;
            }

            $hold->update([
                'status' => Hold::STATUS_EXPIRED,
            ]);

            Log::info('Hold released', [
                'hold_id' => $holdId,
                'product_id' => $hold->product_id,
                'qty' => $hold->qty,
            ]);

            return true;
        });
    }
}


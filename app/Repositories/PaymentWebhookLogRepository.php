<?php

namespace App\Repositories;

use App\Models\PaymentWebhookLog;
use App\Repositories\Contracts\PaymentWebhookLogRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentWebhookLogRepository implements PaymentWebhookLogRepositoryInterface
{
    public function __construct(
        private readonly PaymentWebhookLog $model
    )
    {}

    public function findByIdempotencyKey(string $idempotencyKey): ?PaymentWebhookLog
    {
        return $this->model->where('idempotency_key', $idempotencyKey)->first();
    }

    public function create(array $data): PaymentWebhookLog
    {
        return DB::transaction(function () use ($data) {
            try {
                $log = $this->model->create($data);

                Log::info('Payment webhook log created', [
                    'log_id' => $log->id,
                    'idempotency_key' => $log->idempotency_key,
                    'order_id' => $log->order_id,
                    'status' => $log->status,
                ]);

                return $log;
            } catch (\Illuminate\Database\QueryException $e) {
                // Handle unique constraint violation (duplicate idempotency key)
                if ($e->getCode() === 23000 || str_contains($e->getMessage(), 'Duplicate entry')) {
                    Log::warning('Duplicate idempotency key detected', [
                        'idempotency_key' => $data['idempotency_key'] ?? null,
                    ]);

                    // Return existing log if duplicate
                    return $this->findByIdempotencyKey($data['idempotency_key']);
                }

                throw $e;
            }
        });
    }

    public function markAsProcessed(int $logId, string $status): bool
    {
        return DB::transaction(function () use ($logId, $status) {
            $log = $this->model->where('id', $logId)
                ->lockForUpdate()
                ->first();

            if (! $log) {
                return false;
            }

            $log->update([
                'status' => $status,
                'processed_at' => now(),
            ]);

            Log::info('Payment webhook log marked as processed', [
                'log_id' => $logId,
                'status' => $status,
            ]);

            return true;
        });
    }
}


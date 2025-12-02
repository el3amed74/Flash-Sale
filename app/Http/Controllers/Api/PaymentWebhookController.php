<?php

namespace App\Http\Controllers\Api;

use App\DTOs\PaymentWebhookDTO;
use App\Exceptions\InvalidIdempotencyKeyException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentWebhookRequest;
use App\Services\Contracts\PaymentWebhookServiceInterface;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private PaymentWebhookServiceInterface $webhookService
    ) {
    }

    public function webhook(PaymentWebhookRequest $request): JsonResponse
    {
        try {
            $dto=$request->toDTO();
            $result = $this->webhookService->processWebhook($dto);

            $statusCode = $result['processed'] ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST;

            return response()->json($result, $statusCode);
        } catch (InvalidIdempotencyKeyException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to process webhook',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}


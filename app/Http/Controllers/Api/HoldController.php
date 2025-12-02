<?php

namespace App\Http\Controllers\Api;

use App\DTOs\CreateHoldDTO;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateHoldRequest;
use App\Services\Contracts\HoldServiceInterface;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class HoldController extends Controller
{
    public function __construct(
        private HoldServiceInterface $holdService
    ) {
    }

    public function store(CreateHoldRequest $request): JsonResponse
    {
        try {
            
            $dto= $request->toDTO();
            $hold = $this->holdService->createHold($dto);

            return response()->json([
                'hold_id' => $hold->id,
                'expires_at' => $hold->expires_at->toIso8601String(),
            ], Response::HTTP_CREATED);
            
        } catch (InsufficientStockException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create hold',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}


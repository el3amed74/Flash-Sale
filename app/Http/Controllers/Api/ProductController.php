<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Contracts\ProductServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function __construct(
        private ProductServiceInterface $productService
    ) {
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->productService->getProduct($id);

        if (! $product) {
            return response()->json([
                'message' => 'Product not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json(
            [
                'msg'=>'Product get successfully',
                'product'=> $product
            ],Response::HTTP_OK);
    }
}


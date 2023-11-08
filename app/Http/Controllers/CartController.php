<?php

namespace App\Http\Controllers;

use App\Data\CartProductData;
use App\Exceptions\ProductNotFoundInCartException;
use App\Http\Requests\AddProductInCartRequest;
use App\Http\Requests\RemoveProductFromCartRequest;
use App\Http\Requests\SetCartProductQuantityRequest;
use App\Http\Resources\CartResource;
use App\Repositories\Contracts\CartRepositoryContract;
use App\Repositories\Contracts\ProductRepositoryContract;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    public function __construct(
        protected CartRepositoryContract $cartRepository,
        protected ProductRepositoryContract $productRepository,
    ){

    }
    public function addProductInCart(AddProductInCartRequest $request): JsonResponse
    {
        $data = $request->getData();
        $product = $this->productRepository->find($data);

        if ($product->cart()->exists()) {
            $productQuantity = $product->cart->getQuantity();

            $data = CartProductData::from([
                'product_id' => $product->id,
                'quantity' => $productQuantity + 1,
                'user_id' => $product->cart->user->id,
            ]);
        }

        $item = $this->cartRepository->updateOrCreate($data);

        return (new CartResource($item))->response();
    }

    public function removeProductFromCart(RemoveProductFromCartRequest $request): JsonResponse
    {
        $product = $this->productRepository->find($request->getData());

        if (!$product->cart()->exists()) {
            throw new ProductNotFoundInCartException();
        }

        $productQuantity = $product->cart->getQuantity();

        if ($productQuantity === 1) {
            $this->cartRepository->delete($product->cart);

            return response()->json(['message' => 'Product removed from cart']);
        }

        $data = CartProductData::from([
            'product_id' => $product->id,
            'quantity' => $productQuantity - 1,
            'user_id' => $product->cart->user->id,
        ]);

        $item = $this->cartRepository->updateOrCreate($data);

        return (new CartResource($item))->response();
    }

    public function setCartProductQuantity(SetCartProductQuantityRequest $request)
    {
        $data = $request->getData();
        $product = $this->productRepository->find($data);

        if (!$product->cart()->exists()) {
            throw new ProductNotFoundInCartException();
        }

        $data = CartProductData::from([
            'product_id' => $product->id,
            'quantity' => $data->quantity,
            'user_id' => $product->cart->user->id,
        ]);

        $item = $this->cartRepository->updateOrCreate($data);

        return (new CartResource($item))->response();
    }
}
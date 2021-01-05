<?php

namespace App\Controller;

use App\Classe\Cart;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class StripeController extends AbstractController
{
    /**
     * @Route("/commande/create-session", name="stripe_create_session")
     * @param \App\Classe\Cart $cart
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function index(Cart $cart): JsonResponse
    {
        $YOUR_DOMAIN      = 'http://127.0.0.1:8000';
        $productForStripe = [];

        foreach ($cart->getFull() as $product) {
            $productForStripe[] = [
                'price_data' => [
                    'currency'     => 'eur',
                    'unit_amount'  => $product['product']->getPrice(),
                    'product_data' => [
                        'name'   => $product['product']->getName(),
                        'images' => [$YOUR_DOMAIN . '/uploads/' . $product['product']->getIllustration()],
                    ],
                ],
                'quantity'   => $product['quantity'],
            ];
        }

        Stripe::setApiKey('sk_test_51I5nGIHP4MlDkgKUfdWRVoEw6N6yzkSIlG9XOquCe5yGQFH24wPpdAlxWUJRuiEpczwNKZHdgEKwNM1uNAjof9nU00MXn9eRgS');

        $checkout_session = Session::create(
            [
                'payment_method_types' => ['card'],
                'line_items'           => [$productForStripe],
                'mode'                 => 'payment',
                'success_url'          => $YOUR_DOMAIN . '/success.html',
                'cancel_url'           => $YOUR_DOMAIN . '/cancel.html',
            ]
        );

        $response = new JsonResponse(['id' => $checkout_session->id]);

        return $response;
    }
}

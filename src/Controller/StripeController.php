<?php

namespace App\Controller;

use App\Classe\Cart;
use App\Entity\Order;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class StripeController extends AbstractController
{
    /**
     * @Route("/commande/create-session/{reference}", name="stripe_create_session")
     * @param \App\Classe\Cart $cart
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function index(EntityManagerInterface $entityManager, Cart $cart, $reference): JsonResponse
    {
        $YOUR_DOMAIN      = 'http://127.0.0.1:8000';
        $productForStripe = [];
        $order            = $entityManager->getRepository(Order::class)->findOneByReference($reference);

        foreach ($order->getOrderDetails()->getValues() as $product) {
            $productObject = $entityManager->getRepository(Product::class)->findOneByName($product->getProduct());
            $productForStripe[] = [
                'price_data' => [
                    'currency'     => 'eur',
                    'unit_amount'  => $product->getPrice(),
                    'product_data' => [
                        'name'   => $product->getProduct(),
                        'images' => [$YOUR_DOMAIN . '/uploads/' . $productObject->getIllustration()],
                    ],
                ],
                'quantity'   => $product->getQuantity(),
            ];
        }

        $productForStripe[] = [
            'price_data' => [
                'currency'     => 'eur',
                'unit_amount'  => $order->getCarrierPrice(),
                'product_data' => [
                    'name'   => $order->getCarrierName(),
                    'images' => [$YOUR_DOMAIN],
                ],
            ],
            'quantity'   => 1,
        ];

        Stripe::setApiKey('sk_test_51I5nGIHP4MlDkgKUfdWRVoEw6N6yzkSIlG9XOquCe5yGQFH24wPpdAlxWUJRuiEpczwNKZHdgEKwNM1uNAjof9nU00MXn9eRgS');

        $checkout_session = Session::create(
            [
                'customer_email'       => $this->getUser()->getEmail(),
                'payment_method_types' => ['card'],
                'line_items'           => [$productForStripe],
                'mode'                 => 'payment',
                'success_url'          => $YOUR_DOMAIN . '/commande/merci/{CHECKOUT_SESSION_ID}',
                'cancel_url'           => $YOUR_DOMAIN . '/commande/erreur/{CHECKOUT_SESSION_ID}',
            ]
        );

        $order->setStripeSessionsId($checkout_session->id);
        $entityManager->flush();

        $response = new JsonResponse(['id' => $checkout_session->id]);

        return $response;
    }
}

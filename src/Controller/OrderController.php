<?php

namespace App\Controller;

use App\Classe\Cart;
use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Form\OrderType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/commande", name="order")
     */
    public function index(Cart $cart, Request $request)
    {
        if (!$this->getUser()
                  ->getAddresses()
                  ->getValues()) {
            return $this->redirectToRoute('account_address_add');
        }

        $form = $this->createForm(OrderType::class, null, [
            'user' => $this->getUser(),
        ]);

        return $this->render('order/index.html.twig', [
            'form' => $form->createView(),
            'cart' => $cart->getFull(),
        ]);
    }

    /**
     * @Route("/commande/recapitulatif", name="order_recap", methods={"POST"})
     */
    public function add(Cart $cart, Request $request)
    {
        $form = $this->createForm(OrderType::class, null, [
            'user' => $this->getUser(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $date            = new \DateTime();
            $carriers        = $form->get('carriers')
                                    ->getData();
            $delivery        = $form->get('addresses')
                                    ->getData();
            $deliveryContent = $delivery->getFirstname() . ' ' . $delivery->getLastname();
            $deliveryContent .= '<br>' . $delivery->getPhone();

            if ($delivery->getCompany()) {
                $deliveryContent .= '<br>' . $delivery->getCompany();
            }

            $deliveryContent .= '<br>' . $delivery->getAddress();
            $deliveryContent .= '<br>' . $delivery->getPostal() . ' ' . $delivery->getCity();
            $deliveryContent .= '<br>' . $delivery->getCountry();

            // Enregistrement de la commande
            $order = new Order();
            $reference = $date->format('dmY') . '-' . uniqid();

            $order->setReference($reference);
            $order->setUser($this->getUser());
            $order->setCreatedAt($date);
            $order->setCarrierName($carriers->getName());
            $order->setCarrierPrice($carriers->getPrice());
            $order->setDelivery($deliveryContent);
            $order->setState(0);

            $this->entityManager->persist($order);

            //Enregistrement des produits
            foreach ($cart->getFull() as $product) {
                $orderDetails = new OrderDetails();

                $orderDetails->setMyOrder($order);
                $orderDetails->setProduct($product['product']->getName());
                $orderDetails->setQuantity($product['quantity']);
                $orderDetails->setPrice($product['product']->getPrice());
                $orderDetails->setTotal($product['product']->getPrice() * $product['quantity']);

                $this->entityManager->persist($orderDetails);
            }

            $this->entityManager->flush();

            return $this->render('order/add.html.twig', [
                'cart'      => $cart->getFull(),
                'carrier'   => $carriers,
                'delivery'  => $deliveryContent,
                'reference' => $order->getReference(),
            ]);
        }

        $this->redirectToRoute('cart');
    }
}

<?php

namespace App\Controller;

use App\Classe\Cart;
use App\Classe\Mail;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderSuccessController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("commande/merci/{stripeSessionId}", name="order_success")
     */
    public function index(Cart $cart, $stripeSessionId): Response
    {
        $order = $this->entityManager->getRepository(Order::class)->findOneByStripeSessionsId($stripeSessionId);

        if (!$order || $order->getUser() !== $this->getUser()) {
            return $this->redirectToRoute('home');
        }

        if (!$order->getState() === 0) {
            $cart->remove();
            $order->setState(1);
            $this->entityManager->flush();

            $mail    = new Mail();
            $content = 'Bonjour' . $order->getUser()->getFirstname() . '<br>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Adipisci aliquid consequatur dicta dolore eius eos, error, fugiat iste magnam maiores maxime mollitia, nesciunt nostrum porro similique sit vel voluptates voluptatum?';

            $mail->send($order->getUser()->getEmail(), $order->getUser()->getFirstname(), 'Votre commande La Boutique Française est bien validée !',$content);
        }

        return $this->render('order_success/index.html.twig', [
            'order' => $order
        ]);
    }
}

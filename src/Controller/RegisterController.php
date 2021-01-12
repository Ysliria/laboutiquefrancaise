<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Entity\User;
use App\Form\RegisterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class RegisterController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/inscription", name="register")
     */
    public function index(Request $request, UserPasswordEncoderInterface $encoder): Response
    {
        $notification = null;
        $user         = new User();
        $form         = $this->createForm(RegisterType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user        = $form->getData();
            $searchEmail = $this->entityManager->getRepository(User::class)
                                               ->findOneByEmail($user->getEmail());

            if (!$searchEmail) {
                $password = $encoder->encodePassword($user, $user->getPassword());

                $user->setPassword($password);
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $mail    = new Mail();
                $content = 'Bonjour' . $user->getFirstname() . '<br>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Adipisci aliquid consequatur dicta dolore eius eos, error, fugiat iste magnam maiores maxime mollitia, nesciunt nostrum porro similique sit vel voluptates voluptatum?';

                $mail->send($user->getEmail(), $user->getFirstname(), 'Bienvenue dans La Boutique Française !',$content);

                $notification = "Votre inscription c'est bien déroulé vous pouvez dès à présent vous connecter à votre compte";
            } else {
                $notification = "L'email que vous avez renseigné existe déjà !";
            }
        }

        return $this->render('register/index.html.twig', [
            'form'         => $form->createView(),
            'notification' => $notification,
        ]);
    }
}

<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Entity\ResetPassword;
use App\Entity\User;
use App\Form\ResetPasswordType;
use Doctrine\ORM\EntityManagerInterface;
use PhpParser\Node\Scalar\String_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ResetPasswordController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/mot-de-passe-oublie", name="reset_password")
     */
    public function index(Request $request): Response
    {
        if ($this->getUser()) {
            $this->redirectToRoute('home');
        }

        if ($request->get('email')) {
            $user = $this->entityManager->getRepository(User::class)
                                        ->findOneByEmail($request->get('email'));

            if ($user) {
                $resetPassword = new ResetPassword();

                $resetPassword->setUser($user);
                $resetPassword->setToken(uniqid());
                $resetPassword->getCreatedAt(new \DateTime());
                $this->entityManager->persist($resetPassword);
                $this->entityManager->flush();

                $url     = $this->generateUrl('update_password', [
                    'token' => $resetPassword->getToken()
                ]);
                $content = 'Bonjour ' . $user->getFirstname() . ',<br>Vous avez demandé à réinitialiser votre mot de passe sur La Boutique Française<br><br>';
                $content .= 'Merci de cliquer sur le lien suivant pour <a href="' . $url . '">mettre à jour votre mot de passe.</a>';
                $mail    = new Mail();

                $mail->send($user->getMail(), $user->getFirstname() . ' ' . $user->getLastname, 'Réinitialiser votre mot de passe sur La Boutique Française', $content);
                $this->addFlash('notice', 'Vous allez recevoir pas mail un lien de pour modifier votre mot de passe !');
            } else {
                $this->addFlash('notice', 'Cette adresse email est inconnue !');
            }
        }

        return $this->render('reset_password/index.html.twig');
    }

    /**
     * @Route("/modifier_mon_mot_de_passe{token}", name="update_password")
     * @param \Symfony\Component\HttpFoundation\Request                             $request
     * @param \Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface $encoder
     * @param String                                                                $token
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update(Request $request, UserPasswordEncoderInterface $encoder,string $token): Response
    {
        $resetPassword = $this->entityManager->getRepository()->findOneByToken($token);

        if (!$resetPassword) {
            return $this->redirectToRoute('reset_password');
        }

        $now = new \DateTime();

        if ($now > $resetPassword->getCreatedAt()->modify('+ 3 hour')) {
            $this->addFlash('notice', 'Votre demande de mot de passe a expirée !');

            return $this->redirectToRoute('reset_password');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('new_password')->getData();
            $password    = $encoder->encodePassword($resetPassword->getUser(), $newPassword);

            $resetPassword->getUser()->setPassword($password);
            $this->entityManager->flush();
            $this->addFlash('notice', 'Votre mot de passe a bien été mis à jour');

            return $this->redirectToRoute('app_login');
        } else {
        }

        return $this->render('reset_password/update.html.twig', [
            'form' => $form
        ]);

    }
}

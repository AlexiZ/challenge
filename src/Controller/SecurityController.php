<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/connexion', name: 'app_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils, UserRepository $userRepository): Response
    {
        $token = $request->query->get('token');
        $tokenUser = is_string($token) ? $userRepository->findOneByPersonalToken($token) : null;

        if ($tokenUser) {
            return $this->render('security/login_token_confirm.html.twig', [
                'tokenUser' => $tokenUser,
                'token' => $token,
            ]);
        }

        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/connexion/confirmation', name: 'app_login_token_confirm', methods: ['POST'])]
    public function loginTokenConfirm(): never
    {
        throw new \LogicException('This method can be blank, it will be intercepted by the TokenLoginAuthenticator on the firewall.');
    }

    #[Route('/deconnexion', name: 'app_logout')]
    public function logout(): never
    {
        throw new \LogicException('This method can be blank, it will be intercepted by the logout key on the firewall.');
    }
}

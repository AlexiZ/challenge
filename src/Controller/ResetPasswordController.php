<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private readonly ResetPasswordHelperInterface $resetPasswordHelper,
    ) {
    }

    #[Route('/mot-de-passe-oublie', name: 'app_forgot_password_request')]
    public function request(
        Request $request,
        MailerInterface $mailer,
        UserRepository $userRepository,
        RateLimiterFactory $passwordResetLimiter,
    ): Response {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$passwordResetLimiter->create($request->getClientIp())->consume()->isAccepted()) {
                throw new TooManyRequestsHttpException();
            }

            $user = $userRepository->findOneBy(['email' => $form->get('email')->getData()]);

            if ($user) {
                $this->sendResetPasswordEmail($user, $mailer);
            }

            return $this->redirectToRoute('app_forgot_password_check_email');
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form,
        ]);
    }

    #[Route('/mot-de-passe-oublie/verifiez-vos-emails', name: 'app_forgot_password_check_email')]
    public function checkEmail(): Response
    {
        return $this->render('reset_password/check_email.html.twig');
    }

    #[Route('/mot-de-passe-oublie/reinitialiser/{token}', name: 'app_forgot_password_reset')]
    public function reset(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, ?string $token = null): Response
    {
        if ($token) {
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_forgot_password_reset');
        }

        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw $this->createNotFoundException('Aucun jeton de réinitialisation dans la session.');
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface) {
            $this->addFlash('danger', 'Ce lien de réinitialisation est invalide ou a expiré.');

            return $this->redirectToRoute('app_forgot_password_request');
        }

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->resetPasswordHelper->removeResetRequest($token);

            /** @var User $user */
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $em->flush();

            // Invalidate this browser's session and remember-me cookie so a
            // password reset actually evicts whoever held the old credentials.
            $request->getSession()->invalidate();

            $this->addFlash('success', 'Mot de passe réinitialisé. Vous pouvez vous connecter.');

            $response = $this->redirectToRoute('app_login');
            $response->headers->clearCookie('REMEMBERME', '/');

            return $response;
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form,
        ]);
    }

    private function sendResetPasswordEmail(User $user, MailerInterface $mailer): void
    {
        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface) {
            // Same outward behaviour as "no such user" — don't let the bundle's own
            // per-user throttling exception leak whether the email is registered.
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@challenge-velo.bzh', 'Challenge Vélo'))
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->htmlTemplate('emails/reset_password.html.twig')
            ->context([
                'resetUrl' => $this->generateUrl('app_forgot_password_reset', ['token' => $resetToken->getToken()], UrlGeneratorInterface::ABSOLUTE_URL),
                'resetToken' => $resetToken,
            ]);

        $mailer->send($email);

        $this->setTokenObjectInSession($resetToken);
    }
}

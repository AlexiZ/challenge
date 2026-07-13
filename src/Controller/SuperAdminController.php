<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\CityEdition;
use App\Entity\Edition;
use App\Entity\User;
use App\Form\AdminUserType;
use App\Form\CityType;
use App\Form\CityEditionType;
use App\Form\EditionType;
use App\Repository\CityRepository;
use App\Repository\EditionRepository;
use App\Repository\UserRepository;
use App\Service\SlugGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class SuperAdminController extends AbstractController
{
    #[Route('', name: 'app_super_admin_dashboard')]
    public function dashboard(
        CityRepository $cityRepository,
        EditionRepository $editionRepository,
    ): Response {
        return $this->render('super_admin/dashboard.html.twig', [
            'cities' => $cityRepository->findAll(),
            'editions' => $editionRepository->findAllOrderedByYear(),
        ]);
    }

    // ── Cities ──

    #[Route('/villes/nouvelle', name: 'app_super_admin_city_new')]
    public function newCity(
        Request $request,
        EntityManagerInterface $em,
        SlugGenerator $slugGenerator,
    ): Response {
        $city = new City();
        $form = $this->createForm(CityType::class, $city);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (empty($city->getSlug())) {
                $city->setSlug($slugGenerator->generate($city->getName()));
            }
            $em->persist($city);
            $em->flush();
            $this->addFlash('success', 'Ville créée.');
            return $this->redirectToRoute('app_super_admin_dashboard');
        }

        return $this->render('super_admin/city_form.html.twig', [
            'form' => $form,
            'city' => $city,
        ]);
    }

    #[Route('/villes/{id}/modifier', name: 'app_super_admin_city_edit')]
    public function editCity(City $city, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CityType::class, $city);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Ville mise à jour.');
            return $this->redirectToRoute('app_super_admin_dashboard');
        }

        return $this->render('super_admin/city_form.html.twig', [
            'form' => $form,
            'city' => $city,
        ]);
    }

    #[Route('/villes/{id}/supprimer', name: 'app_super_admin_city_delete', methods: ['POST'])]
    public function deleteCity(City $city, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_city_' . $city->getId(), $request->request->get('_token'))) {
            $em->remove($city);
            $em->flush();
            $this->addFlash('success', 'Ville supprimée.');
        }
        return $this->redirectToRoute('app_super_admin_dashboard');
    }

    // ── Editions ──

    #[Route('/editions/nouvelle', name: 'app_super_admin_edition_new')]
    public function newEdition(Request $request, EntityManagerInterface $em): Response
    {
        $edition = new Edition();
        $form = $this->createForm(EditionType::class, $edition);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($edition);
            $em->flush();
            $this->addFlash('success', 'Édition créée.');
            return $this->redirectToRoute('app_super_admin_dashboard');
        }

        return $this->render('super_admin/edition_form.html.twig', [
            'form' => $form,
            'edition' => $edition,
        ]);
    }

    #[Route('/editions/{id}/supprimer', name: 'app_super_admin_edition_delete', methods: ['POST'])]
    public function deleteEdition(Edition $edition, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_edition_' . $edition->getId(), $request->request->get('_token'))) {
            $em->remove($edition);
            $em->flush();
            $this->addFlash('success', 'Édition supprimée.');
        }
        return $this->redirectToRoute('app_super_admin_dashboard');
    }

    #[Route('/editions/{id}/modifier', name: 'app_super_admin_edition_edit')]
    public function editEdition(Edition $edition, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(EditionType::class, $edition);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Édition mise à jour.');
            return $this->redirectToRoute('app_super_admin_dashboard');
        }

        return $this->render('super_admin/edition_form.html.twig', [
            'form' => $form,
            'edition' => $edition,
        ]);
    }

    // ── CityEditions ──

    #[Route('/participation/nouvelle', name: 'app_super_admin_city_edition_new')]
    public function newCityEdition(Request $request, EntityManagerInterface $em): Response
    {
        $cityEdition = new CityEdition();
        $form = $this->createForm(CityEditionType::class, $cityEdition);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($cityEdition);
            $em->flush();
            $this->addFlash('success', 'Participation ville/édition créée.');
            return $this->redirectToRoute('app_super_admin_dashboard');
        }

        return $this->render('super_admin/city_edition_form.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/participation/{id}/supprimer', name: 'app_super_admin_city_edition_delete', methods: ['POST'])]
    public function deleteCityEdition(CityEdition $cityEdition, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_city_edition_' . $cityEdition->getId(), $request->request->get('_token'))) {
            $em->remove($cityEdition);
            $em->flush();
            $this->addFlash('success', 'Participation supprimée.');
        }
        return $this->redirectToRoute('app_super_admin_dashboard');
    }

    #[Route('/participation/{id}/modifier', name: 'app_super_admin_city_edition_edit')]
    public function editCityEdition(CityEdition $cityEdition, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CityEditionType::class, $cityEdition);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Participation mise à jour.');
            return $this->redirectToRoute('app_super_admin_dashboard');
        }

        return $this->render('super_admin/city_edition_form.html.twig', [
            'form' => $form,
            'cityEdition' => $cityEdition,
        ]);
    }

    // ── Users ──

    #[Route('/utilisateurs', name: 'app_super_admin_users')]
    public function users(UserRepository $userRepository): Response
    {
        return $this->render('super_admin/users.html.twig', [
            'users' => $userRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/utilisateurs/nouveau', name: 'app_super_admin_user_new')]
    public function newUser(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
    ): Response {
        $user = new User();
        $form = $this->createForm(AdminUserType::class, $user, ['require_password' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur créé.');
            return $this->redirectToRoute('app_super_admin_users');
        }

        return $this->render('super_admin/user_form.html.twig', [
            'form' => $form,
            'editedUser' => $user,
        ]);
    }

    #[Route('/utilisateurs/{id}/modifier', name: 'app_super_admin_user_edit')]
    public function editUser(
        User $editedUser,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
    ): Response {
        $form = $this->createForm(AdminUserType::class, $editedUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = $form->get('plainPassword')->getData();
            if ($plain) {
                $editedUser->setPassword($hasher->hashPassword($editedUser, $plain));
            }
            $em->flush();
            $this->addFlash('success', 'Utilisateur mis à jour.');
            return $this->redirectToRoute('app_super_admin_users');
        }

        return $this->render('super_admin/user_form.html.twig', [
            'form' => $form,
            'editedUser' => $editedUser,
        ]);
    }

    #[Route('/utilisateurs/{id}/supprimer', name: 'app_super_admin_user_delete', methods: ['POST'])]
    public function deleteUser(User $editedUser, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_user_' . $editedUser->getId(), $request->request->get('_token'))) {
            $em->remove($editedUser);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé.');
        }
        return $this->redirectToRoute('app_super_admin_users');
    }
}

<?php

namespace App\Controller;

use App\Entity\Recipe;
use App\Entity\User;
use App\Form\ChangepasswordType;
use App\Repository\RecipeRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Form\UserFormType;


class AccountController extends AbstractController
{
    #[Route('/account', name: 'app_recipe_profile')]
    public function profile(EntityManagerInterface $em): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_recipe_login');
        }
        $user = $this->getUser();
        $recipes = $em->getRepository(Recipe::class)->findBy(['user' => $user]);

        return $this->render('recipe/profile.html.twig', [
            'user' => $user,
            'recipes' => $recipes,
        ]);
    }

    #[Route('/account/edit', name: "app_account_edit")]
    public function edit(Request $request, EntityManagerInterface $em, TranslatorInterface $translator): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('error', 'You must login to edit an account');
            return $this->redirectToRoute('app_recipe_login');
        }
        $user=$this->getUser();
        $form = $this->createForm(UserFormType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user->setUpdatedAt(new DateTimeImmutable());
            $em->flush();
            $this->addFlash('success', $translator->trans('Account successfuly updated !'));
            return $this->redirectToRoute('app_recipe_profile');
        }
        return $this->render('account/edit.html.twig', [
            'user' => $user,
            'monForm' => $form->createView()
        ]);
    }

    public function lastThree(RecipeRepository $recipeRepository): Response
    {
        $recipes = $recipeRepository->findBy([], ['createdAt' => 'DESC'], 3);

        return $this->render('home/index.html.twig', [
            'recipes' => $recipes,
        ]);
    }

    #[Route('/delete-account', name: 'app_recipe_account_delete')]
    public function account(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('delete_account', $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();

            $this->container->get('security.token_storage')->setToken(null);
            $request->getSession()->invalidate();

            return $this->redirectToRoute('home');
        }

        return $this->redirectToRoute('home');
    }

    #[Route('/change-password', name: 'app_recipe_changepassword')]
    public function changePassword(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, TranslationTranslatorInterface $translator): Response
    {
        $form = $this->createForm(ChangepasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $currentPassword = $form->get('password')->getData();
            $user = $this->getUser();

            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', $this->$translator->trans('user.current_password_incorrect'));
                return $this->redirectToRoute('app_recipe_profile');
            }

            $newPassword = $data['newPassword'];
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', $this->$translator->trans('user.password_changed_successfully'));
            return $this->redirectToRoute('app_recipe_profile');
        }
        return $this->redirectToRoute('app_recipe_profile');
    }
}

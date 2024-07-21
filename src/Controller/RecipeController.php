<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\RecipeRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Recipe;
use App\Entity\User;
use App\Form\ChangepasswordType;
use App\Form\DeleteAccountFormType;
use DateTimeImmutable;
use App\Form\RecipeType;
use App\Form\SearchType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Translation\LocaleSwitcher;

#[Route('{_locale<%app.supported_locales%>}')]

class RecipeController extends AbstractController
{

    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    #[Route(path: '/recette', name: 'app_recipe_index')]
    public function index(Request $request, RecipeRepository $repostitory, EntityManagerInterface $em): Response
    {

        if ($this->getUser()) {
            if (!$this->getUser()->isVerified()) {
                $this->addFlash('warning', $this->translator->trans('user.not_verified'));
            }
        }

        $recipe = new Recipe();


        $userRepo = $em->getRepository(User::class);
        $user = $userRepo->find(1);
        $recipe->setTitle("Riz Frit aux Légumes")
            ->setSlug("riz-frit-legumes")
            ->setContent("Un plat asiatique savoureux avec du riz sauté, des légumes croquants et des assaisonnements aromatiques.")
            ->setDuration(35)
            ->setCreatedAt(new DateTimeImmutable())
            ->setUpdatedAt(new DateTimeImmutable())



            ->setUser($user);

        $em->persist($recipe);

        $sort = $request->query->get('sort', 'default');
        $recipes = $em->getRepository(Recipe::class)->findAllSorted($sort);



        return $this->render(
            'recipe/index.html.twig',
            ['recipes' => $recipes]
        );
    }
    #[Route(path: '/recette/{slug}-{id}', name: 'app_recipe_show', requirements: ['id' => '\d+', 'slug' => '[a-z0-9-]+'])]
    public function show(Request $request, string $slug, int $id, RecipeRepository $repostitory): Response
    {

        $recipe = $repostitory->find($id);

        if (!$recipe) {
            return $this->render('recipe/404.html.twig');
        }

        if ($recipe->getSlug() !== $slug) {
            return $this->redirectToRoute("app_recipe_show", ['id' => $recipe->getID(), 'slug' => $recipe->getSlug()]);
        }
        return $this->render('recipe/show.html.twig', [
            'slug' => $slug,
            'id' => $id,
            'recipe' => $recipe,
            'user' => [
                "firstname" => "John",
                "lastname" => "Doe"
            ]
        ]);
    }

    #[Route(path: '/recette/{id}/edit', name: 'app_recipe_edit')]
    public function edit(Recipe $recipe, Request $request, EntityManagerInterface $em): Response
    {

        if ($this->getUser()) {
            if (!$this->getUser()->isVerified()) {
                $this->addFlash('error', $this->translator->trans('user.must_confirm_email_edit'));
                return $this->redirectToRoute("app_recipe_index");
            } else if ($recipe->getUser()->getEmail() !== $this->getUser()->getEmail()) {
                $this->addFlash('error', $this->translator->trans('user.cannot_modify_recipe'));
            }
        } else {
            $this->addFlash('error', $this->translator->trans('user.must_login_edit'));
            return $this->redirectToRoute("app_recipe_login");
        }


        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);
        $titre = $recipe->getTitle();
        if ($form->isSubmitted() && $form->isValid()) {
            $recipe->setUpdatedAt(new DateTimeImmutable());
            $em->flush();
            $this->addFlash("success", $this->translator->trans("recipes.edited_successfully", ['{{ title }}' => $titre]));
            return $this->redirectToRoute('app_recipe_index');
        }
        return $this->render('recipe/edit.html.twig', [
            'recipe' => $recipe,
            'form' => $form
        ]);
    }

    #[Route(path: '/recette/create', name: 'app_recipe_create')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {

        if ($this->getUser()) {
            if (!$this->getUser()->isVerified()) {
                $this->addFlash('warning', $this->translator->trans('user.must_confirm_email_create'));
                return $this->redirectToRoute("app_recipe_index");
            }
        } else {
            $this->addFlash('error', $this->translator->trans('user.must_login_create'));
            return $this->redirectToRoute("app_recipe_login");
        }
        $recipe = new Recipe;
        $titre = $recipe->getTitle();

        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $recipe->setUser($this->getUser());
            $recipe->setCreatedAt(new DateTimeImmutable());
            $recipe->setUpdatedAt(new DateTimeImmutable());
            $em->persist($recipe);
            $em->flush();
            $this->addFlash('success', $this->translator->trans('recipes.created_successfully', ['{{ title }}' => $titre]));
            return $this->redirectToRoute('app_recipe_index');
        }
        return $this->render('recipe/create.html.twig', [
            'recipe' => $recipe,
            'form' => $form
        ]);
    }

    #[Route(path: '/recette/{id}/delete', name: 'app_recipe_delete')]
    public function delete(Recipe $recipe, Request $request, EntityManagerInterface $em): Response
    {

        if ($this->getUser()) {
            if (!$this->getUser()->isVerified()) {
                $this->addFlash('error', $this->translator->trans('user.must_confirm_email_edit'));
                return $this->redirectToRoute("app_recipe_index");
            } else if ($recipe->getUser()->getEmail() !== $this->getUser()->getEmail()) {
                $this->addFlash('error', $this->translator->trans('user.cannot_delete_recipe'));
                return $this->redirectToRoute("app_recipe_login");
            }
        } else {
            $this->addFlash('error', $this->translator->trans('user.must_login_edit'));
            return $this->redirectToRoute("app_recipe_login");
        }

        $titre = $recipe->getTitle();
        $em->remove($recipe);
        $em->flush();
        $this->addFlash("danger", $this->translator->trans('recipes.deleted_successfully', ['{{ title }}' => $titre]));
        return $this->redirectToRoute('app_recipe_index');
    }


    #[Route(path: '/search', name: 'app_recipe_search', requirements: ['id' => '\d+', 'slug' => '[a-z0-9-]+'])]
    public function search(Request $request, RecipeRepository $recipeRepository): Response
    {
        $query = $request->query->get('q');
        $recipes = $recipeRepository->findByQuery($query);

        if (!empty($query)) {
            $recipes = $recipeRepository->findByQuery($query);
        }

        return $this->render('recipe/search.html.twig', [
            'recipes' => $recipes,
            'query' => $query,
        ]);
    }
}
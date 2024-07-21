<?php

namespace App\Controller;

use App\Entity\Recipe;
use App\Entity\User;
use App\Form\LoginType;
use App\Form\RegistrationFormType;
use App\Form\SearchType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\BrowserKit\Request as BrowserKitRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Attribute\Route;
use DateTimeImmutable;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Form\FormError;
use Symfony\Component\Mime\Address;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Security\EmailVerifier;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('{_locale<%app.supported_locales%>}')]

class HomeController extends AbstractController
{

    private EmailVerifier $emailVerifier;
    private $translator;

    public function __construct(EmailVerifier $emailVerifier, TranslatorInterface $translator) 
    {
        $this->emailVerifier = $emailVerifier;
        $this->translator = $translator;
    }


    #[Route(path: "/", name: "home")]
    public function index(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
{  
    $user = new User();
    $form = $this->createForm(RegistrationFormType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $form->get('password')->getData()
        );
        $user->setPassword($hashedPassword);

        $user->setUpdatedAt(new DateTimeImmutable());
        $user->setCreatedAt(new DateTimeImmutable());
        $em->persist($user);
        $em->flush();
        
        $welcomeMessage = $this->translator->trans('home.welcome', ['%firstname%' => $user->getFirstname()]);
        $verifyEmailMessage = $this->translator->trans('home.verifyemail');
        $this->addFlash('success', $welcomeMessage . $verifyEmailMessage);
        $emailContent = $this->translator->trans('email.confirm_content');


        $this->emailVerifier->sendEmailConfirmation(
            'app_verify_email',
            $user,
            (new TemplatedEmail())
                ->from(new Address('noreply@chezgusteau.com', 'Chez Gusteau'))
                ->to($user->getEmail())
                ->subject($emailContent)
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );
        
        return $this->redirectToRoute('app_recipe_login');
    }

    $recipes = $em->getRepository(Recipe::class)->findAll();

    return $this->render('home/index.html.twig', [
        'recipes' => $recipes,
        'registrationForm' => $form
    ]);
    }



    #[Route(path: '/login', name: 'app_recipe_login')]
    public function login(AuthenticationUtils $authenticationUtils, EntityManagerInterface $em): Response
    {
        if ($this->getUser()) {
            $this->addFlash(
            'warning',
            $this->translator->trans('login.alreadyconnected'));
            return $this->redirectToRoute('app_recipe_profile');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        $recipes = $em->getRepository(Recipe::class)->findAll();

        

        return $this->render('recipe/login.html.twig', [
            'recipes' => $recipes,
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/terms', name: 'app_recipe_terms')]
    public function terms(Request $request): Response
    {        

        return $this->render('recipe/terms.html.twig', [
        ]);
    }
    #[Route(path: '/404', name: 'app_recipe_error')]
    public function show404(): Response
    {
        return $this->render('recipe/404.html.twig');
    }

    #[Route(path: '/logout', name: 'app_recipe_logout')]
    public function logout(): void
    {
    }
}

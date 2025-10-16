<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/registration', name: 'userRegistration')]
    public function userRegistration(Request $request, UserPasswordHasherInterface $passwordhasher): Response
    {
        $user = new User();
        $registration_form = $this->createForm(UserType::class, $user);
        $registration_form->handleRequest($request);

        if ($registration_form->isSubmitted() && $registration_form->isValid()) {
            $plaintextPassword = $registration_form->get('password')->getData();
            $hashedPassword = $passwordhasher->hashPassword($user, $plaintextPassword);
            $user->setPassword($hashedPassword);

            $user->setRoles(['ROLE_USER']);
            $this->em->persist($user);
            $this->em->flush();

            return $this->redirectToRoute('userRegistration'); // Redirige a alguna ruta después del registro
        }
        return $this->render('user/index.html.twig', [
            'registration_form' => $registration_form->createView(),
        ]);
    }
}

<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Form\PostType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Dom\Entity;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class PostController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/', name: 'app_post')]
    public function index(Request $request, SluggerInterface $slugger, PaginatorInterface $paginator): Response
    {
        $post = new Post();
        $query = $this->em->getRepository(Post::class)->findAllPosts();

        $pagination = $paginator->paginate(
        $query, /* query NOT result */
        $request->query->getInt('page', 1), /* page number */
        2 /* limit per page */
    );
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            $url = str_replace(' ', '-', $form->get('title')->getData());

            if ($file) {
                $originFile = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $fileName = $slugger->slug($originFile);
                $newFileName = $fileName.'-'.uniqid().'.'.$file->guessExtension();

                try {
                    $file->move($this->getParameter('files_directory'), $newFileName);
                } catch (FileException $e) {
                    throw new \Exception('Error al subir el archivo');
                }
                $post->setFile($newFileName);

            }
            $post->setUrl($url);
            $user = $this->em->getRepository(User::class)->find(1);
            $post->setUser($user);
            $this->em->persist($post);
            $this->em->flush();

            return $this->redirectToRoute('app_post');
        }
        return $this->render('post/index.html.twig', [
            'form' => $form->createView(),
            'posts' => $pagination,
        ]);
    }

    #[Route('/post/details/{id}', name: 'postDetails')]
    public function postDetails(Post $post)
    {
        return $this->render('post/post-details.html.twig', ['post' => $post]);
    }

    #[Route('/Likes', name: 'Likes', options: ['expose' => true])]

    public function Like(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $em = $this->em;
            $user = $this->getUser();
            $id = $request->request->get('id');
            $post = $em->getRepository(Post::class)->find($id);
            $Likes = $post->getLikes();

            if ($user instanceof User) {
                $Likes .= $user->getId() . ',';
                $post->setLikes($Likes);
                $em->flush();
                return new Response(json_encode(['likes' => $Likes]));
            } else {
                return new Response('User not authenticated', 403);
            }

        }else {
            return new Response('Error');
        }
    }
}

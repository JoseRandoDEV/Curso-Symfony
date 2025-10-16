<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function remove(Post $entity, bool $flush = false): void
    {
        $this->em->remove($entity);
        if ($flush) {
            $this->em->flush();
        }
    }

    public function findAllPosts()
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT post.id, post.title, post.description, post.file, post.creation_date, post.url
                FROM App\Entity\Post post
                ORDER BY post.id DESC'
            );
    }

}

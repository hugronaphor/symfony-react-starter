<?php

namespace App\Tests\Fixtures;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class UserFactory
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PasswordHasherFactoryInterface $passwordHasherFactory,
    ) {
    }

    /**
     * Create a regular user.
     *
     * @param array<string, mixed> $data
     */
    public function createUser(array $data = []): User
    {
        return $this->create($data + ['roles' => ['ROLE_USER']]);
    }

    /**
     * Create an admin user.
     *
     * @param array<string, mixed> $data
     */
    public function createAdmin(array $data = []): User
    {
        return $this->create($data + ['roles' => ['ROLE_ADMIN']]);
    }

    /**
     * Create multiple users at once.
     *
     * @param array<string, mixed> $data
     *
     * @return list<User>
     */
    public function createMultiple(int $count, array $data = []): array
    {
        $users = [];
        for ($i = 0; $i < $count; ++$i) {
            $users[] = $this->createUser($data + [
                'email' => sprintf('user-%d@example.com', $i),
            ]);
        }

        return $users;
    }

    /**
     * Base creation method.
     *
     * @param array<string, mixed> $data
     */
    private function create(array $data = []): User
    {
        $user = new User();
        $user->setEmail($data['email'] ?? 'user-'.uniqid().'@example.com');
        $user->setRoles($data['roles'] ?? ['ROLE_USER']);

        $passwordHasher = $this->passwordHasherFactory->getPasswordHasher(User::class);
        $user->setPassword($passwordHasher->hash($data['password'] ?? 'password123'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}

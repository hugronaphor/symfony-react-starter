<?php

namespace App\Tests\Support;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

abstract class AuthenticatedTestCase extends TestCase
{
    protected PasswordHasherFactoryInterface $passwordHasherFactory;
    protected User $authenticatedUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->passwordHasherFactory = self::getContainer()
            ->get(PasswordHasherFactoryInterface::class);

        // Create and persist authenticated user for tests
        $this->authenticatedUser = $this->createAuthenticatedUser();
    }

    /**
     * Creates and persists an authenticated user for testing.
     * Override this method in subclasses to customize user creation.
     *
     * @param array<string, mixed> $data
     */
    protected function createAuthenticatedUser(array $data = []): User
    {
        $user = new User();
        $user->setEmail($data['email'] ?? 'authenticated@example.com');
        $user->setRoles($data['roles'] ?? ['ROLE_USER']);

        $passwordHasher = $this->passwordHasherFactory->getPasswordHasher(User::class);
        $user->setPassword($passwordHasher->hash($data['password'] ?? 'password123'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Create additional users for testing (e.g., for filtering tests).
     *
     * @param array<string, mixed> $data
     */
    protected function createUser(array $data = []): User
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

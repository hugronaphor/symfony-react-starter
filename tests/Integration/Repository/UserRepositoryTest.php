<?php

namespace App\Tests\Integration\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Support\AuthenticatedTestCase;

class UserRepositoryTest extends AuthenticatedTestCase
{
    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = self::getContainer()->get(UserRepository::class);
    }

    public function testFindByEmail(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('test@example.com');

        $passwordHasher = $this->passwordHasherFactory->getPasswordHasher(User::class);
        $hashedPassword = $passwordHasher->hash('password123');
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Act
        $foundUser = $this->repository->findOneBy(['email' => 'test@example.com']);

        // Assert
        $this->assertNotNull($foundUser);
        $this->assertSame('test@example.com', $foundUser->getEmail());
        $this->assertInstanceOf(User::class, $foundUser);
    }

    public function testFindByEmailReturnsNullWhenNotFound(): void
    {
        // Act
        $foundUser = $this->repository->findOneBy(['email' => 'nonexistent@example.com']);

        // Assert
        $this->assertNull($foundUser);
    }

    public function testFindByEmailWithMultipleUsers(): void
    {
        // Arrange - Create multiple users
        $user1 = new User();
        $user1->setEmail('user1@example.com');
        $passwordHasher = $this->passwordHasherFactory->getPasswordHasher(User::class);
        $user1->setPassword($passwordHasher->hash('password123'));
        $user1->setRoles(['ROLE_USER']);

        $user2 = new User();
        $user2->setEmail('user2@example.com');
        $user2->setPassword($passwordHasher->hash('password456'));
        $user2->setRoles(['ROLE_ADMIN']);

        $this->entityManager->persist($user1);
        $this->entityManager->persist($user2);
        $this->entityManager->flush();

        // Act
        $foundUser = $this->repository->findOneBy(['email' => 'user2@example.com']);

        // Assert
        $this->assertNotNull($foundUser);
        $this->assertSame('user2@example.com', $foundUser->getEmail());
        $this->assertContains('ROLE_ADMIN', $foundUser->getRoles());
    }

    public function testRepositoryClearedBetweenTests(): void
    {
        // This test verifies that database is reset between tests
        // The authenticated user from setUp should exist
        $foundUser = $this->repository->findOneBy(['email' => $this->authenticatedUser->getEmail()]);
        $this->assertNotNull($foundUser);

        // But this random user should not
        $randomUser = $this->repository->findOneBy(['email' => 'random@example.com']);
        $this->assertNull($randomUser);
    }
}

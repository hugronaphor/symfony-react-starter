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

    public function testUpgradePasswordUpdatesPasswordInDatabase(): void
    {
        $oldPassword = $this->authenticatedUser->getPassword();
        $newHashedPassword = 'new_hashed_password_123';

        $this->repository->upgradePassword($this->authenticatedUser, $newHashedPassword);

        $this->entityManager->clear();

        $updatedUser = $this->repository->findOneBy(['email' => $this->authenticatedUser->getEmail()]);

        $this->assertNotNull($updatedUser);
        $this->assertSame($newHashedPassword, $updatedUser->getPassword());
        $this->assertNotSame($oldPassword, $updatedUser->getPassword());
    }

    public function testUpgradePasswordPersistsChanges(): void
    {
        $newHashedPassword = 'another_new_password_456';

        $this->repository->upgradePassword($this->authenticatedUser, $newHashedPassword);

        $this->entityManager->clear();

        $reloadedUser = $this->repository->find($this->authenticatedUser->getId());

        $this->assertNotNull($reloadedUser);
        $this->assertSame($newHashedPassword, $reloadedUser->getPassword());
    }

    public function testFindByEmailWithSpecialCharacters(): void
    {
        $specialEmail = 'test+special@example.com';

        $user = new User();
        $user->setEmail($specialEmail);
        $passwordHasher = $this->passwordHasherFactory->getPasswordHasher(User::class);
        $user->setPassword($passwordHasher->hash('password123'));
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $foundUser = $this->repository->findOneBy(['email' => $specialEmail]);

        $this->assertNotNull($foundUser);
        $this->assertSame($specialEmail, $foundUser->getEmail());
    }

    public function testFindByEmailWithUppercaseCharacters(): void
    {
        $email = 'Test@Example.COM';

        $user = new User();
        $user->setEmail($email);
        $passwordHasher = $this->passwordHasherFactory->getPasswordHasher(User::class);
        $user->setPassword($passwordHasher->hash('password123'));
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $foundUser = $this->repository->findOneBy(['email' => $email]);

        $this->assertNotNull($foundUser);
        $this->assertSame($email, $foundUser->getEmail());
    }

    public function testFindByEmailIsCaseSensitive(): void
    {
        $email = 'Test@Example.com';

        $user = new User();
        $user->setEmail($email);
        $passwordHasher = $this->passwordHasherFactory->getPasswordHasher(User::class);
        $user->setPassword($passwordHasher->hash('password123'));
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $foundUser = $this->repository->findOneBy(['email' => 'test@example.com']);

        $this->assertNull($foundUser);

        $correctUser = $this->repository->findOneBy(['email' => $email]);

        $this->assertNotNull($correctUser);
    }
}

<?php

namespace App\Tests\Functional\Controller\Api;

use App\Entity\User;
use App\Tests\Support\FunctionalTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class ProfileControllerTest extends FunctionalTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        static::initializeSchema();
    }

    /**
     * @param array<string> $roles
     */
    private function createUser(string $email, string $password, array $roles = ['ROLE_USER']): User
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $passwordHasherFactory = $container->get(PasswordHasherFactoryInterface::class);
        $passwordHasher = $passwordHasherFactory->getPasswordHasher(User::class);

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hash($password));
        $user->setRoles($roles);

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    public function testGetProfileWhenAuthenticatedReturns200WithUserData(): void
    {
        $email = 'authenticated@example.com';
        $password = 'password123';
        $this->createUser($email, $password);

        $this->authenticateUser($this->client, $email, $password);

        $this->client->request('GET', '/api/profile/me');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $responseData = json_decode($this->client->getResponse()->getContent() ?: '', true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('email', $responseData);
        $this->assertArrayHasKey('roles', $responseData);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertSame($email, $responseData['email']);
        $this->assertContains('ROLE_USER', $responseData['roles']);
    }

    public function testGetProfileWhenNotAuthenticatedReturns401(): void
    {
        $this->client->request('GET', '/api/profile/me');

        $this->assertResponseStatusCodeSame(401);

        $response = $this->client->getResponse();
        $this->assertNotFalse($response->getContent());

        $responseData = json_decode($response->getContent(), true);

        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testProfileEndpointUsesCorrectSerializationGroups(): void
    {
        $email = 'test@example.com';
        $password = 'password123';

        $this->createUser($email, $password, ['ROLE_USER', 'ROLE_ADMIN']);
        $this->authenticateUser($this->client, $email, $password);

        $this->client->request('GET', '/api/profile/me');

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent() ?: '', true);

        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('email', $responseData);
        $this->assertArrayHasKey('roles', $responseData);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayNotHasKey('password', $responseData);
    }

    public function testProfileEndpointReturnsJsonContentType(): void
    {
        $email = 'test@example.com';
        $password = 'password123';

        $this->createUser($email, $password);
        $this->authenticateUser($this->client, $email, $password);

        $this->client->request('GET', '/api/profile/me');

        $this->assertResponseIsSuccessful();
        $this->assertTrue(
            $this->client->getResponse()->headers->contains('Content-Type', 'application/json')
        );
    }
}

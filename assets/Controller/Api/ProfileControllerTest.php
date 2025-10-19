<?php

namespace assets\Controller\Api;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfileControllerTest extends WebTestCase
{
    private $client;
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        // Clear any existing users
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $em->createQuery('DELETE FROM App\Entity\User')->execute();
    }
    //
    //    public function testMeEndpointReturnsUserDataWhenAuthenticated(): void
    //    {
    //        // Create a test user
    //        $user = $this->createUser('profile@example.com', 'password123');
    //
    //        // Login as the user
    //        $this->client->loginUser($user);
    //
    //        // Make request to profile endpoint
    //        $this->client->request('GET', '/api/profile/me');
    //
    //        $this->assertResponseIsSuccessful();
    //        $this->assertResponseHeaderSame('content-type', 'application/json');
    //
    //        $data = json_decode($this->client->getResponse()->getContent(), true);
    //
    //        // Check the response structure
    //        $this->assertArrayHasKey('id', $data);
    //        $this->assertArrayHasKey('email', $data);
    //        $this->assertArrayHasKey('roles', $data);
    //
    //        // Verify the data
    //        $this->assertSame($user->getId(), $data['id']);
    //        $this->assertSame('profile@example.com', $data['email']);
    //        $this->assertContains('ROLE_USER', $data['roles']);
    //    }
    //
    //    public function testMeEndpointReturns401WhenNotAuthenticated(): void
    //    {
    //        $this->client->request('GET', '/api/profile/me');
    //
    //        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    //
    //        $data = json_decode($this->client->getResponse()->getContent(), true);
    //        $this->assertSame('Not authenticated', $data['message']);
    //    }
    //
    //    public function testSerializationGroupsWorkCorrectly(): void
    //    {
    //        // Create a user with specific data
    //        $user = new User();
    //        $user->setEmail('groups@example.com');
    //        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
    //        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
    //
    //        $em = static::getContainer()->get('doctrine.orm.entity_manager');
    //        $em->persist($user);
    //        $em->flush();
    //
    //        // Login as the user
    //        $this->client->loginUser($user);
    //
    //        // Make request
    //        $this->client->request('GET', '/api/profile/me');
    //
    //        $data = json_decode($this->client->getResponse()->getContent(), true);
    //
    //        // These fields should be present (part of 'profile' group)
    //        $this->assertArrayHasKey('id', $data);
    //        $this->assertArrayHasKey('email', $data);
    //        $this->assertArrayHasKey('roles', $data);
    //
    //        // Password should never be in the response
    //        $this->assertArrayNotHasKey('password', $data);
    //    }
    //
    //    public function testProfileEndpointHandlesJsonResponse(): void
    //    {
    //        $user = $this->createUser('json@example.com', 'password123');
    //
    //        $this->client->loginUser($user);
    //        $this->client->request('GET', '/api/profile/me', [], [], [
    //            'HTTP_ACCEPT' => 'application/json',
    //        ]);
    //
    //        $this->assertResponseIsSuccessful();
    //
    //        // Verify it's valid JSON
    //        $content = $this->client->getResponse()->getContent();
    //        $this->assertJson($content);
    //
    //        // Verify the structure
    //        $data = json_decode($content, true);
    //        $this->assertIsArray($data);
    //        $this->assertArrayHasKey('email', $data);
    //    }
    //
    //    private function createUser(string $email, string $password): User
    //    {
    //        $user = new User();
    //        $user->setEmail($email);
    //        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
    //        $user->setRoles(['ROLE_USER']);
    //
    //        $em = static::getContainer()->get('doctrine.orm.entity_manager');
    //        $em->persist($user);
    //        $em->flush();
    //
    //        return $user;
    //    }
}

<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Tests\Support\FunctionalTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class SecurityControllerTest extends FunctionalTestCase
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

    public function testLoginWithValidCredentials(): void
    {
        $email = 'test@example.com';
        $password = 'password123';

        $this->createUser($email, $password);

        $crawler = $this->client->request('GET', '/login');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Sign In')->form([
            'email' => $email,
            'password' => $password,
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/');
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testLoginWithInvalidPassword(): void
    {
        $email = 'test@example.com';
        $password = 'correctpassword';

        $this->createUser($email, $password);

        $crawler = $this->client->request('GET', '/login');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Sign In')->form([
            'email' => $email,
            'password' => 'wrongpassword',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();

        $this->assertSelectorExists('[data-test="error-message"]');
        $this->assertSelectorTextContains('[data-test="error-message"]', 'Invalid credentials');
    }

    public function testLoginWithMissingCsrfToken(): void
    {
        $this->client->request('POST', '/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();

        $this->assertSelectorExists('[data-test="error-message"]');
        $this->assertSelectorTextContains('[data-test="error-message"]', 'CSRF');
    }

    public function testLoginWithInvalidCsrfToken(): void
    {
        $this->client->request('POST', '/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
            '_csrf_token' => 'invalid_token',
        ]);

        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();

        $this->assertSelectorExists('[data-test="error-message"]');
        $this->assertSelectorTextContains('[data-test="error-message"]', 'CSRF');
    }

    public function testLoginWithInvalidEmail(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Sign In')->form([
            'email' => 'nonexistent@example.com',
            'password' => 'somepassword',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();

        $this->assertSelectorExists('[data-test="error-message"]');
        $this->assertSelectorTextContains('[data-test="error-message"]', 'Invalid credentials');
    }

    public function testAlreadyAuthenticatedUserVisitingLoginRedirectsToApp(): void
    {
        $email = 'test@example.com';
        $password = 'password123';

        $this->createUser($email, $password);
        $this->authenticateUser($this->client, $email, $password);

        // Now request the login page - should redirect since already authenticated
        $this->client->request('GET', '/login');

        $this->assertResponseRedirects('/');
    }

    public function testLogoutDestroysSessionAndRedirectsToLogin(): void
    {
        $email = 'test@example.com';
        $password = 'password123';

        $this->createUser($email, $password);
        $this->authenticateUser($this->client, $email, $password);

        // Verify we can access protected page
        $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        // Logout
        $this->client->request('GET', '/logout');

        $this->assertResponseRedirects('/login');

        // Verify we can't access protected page anymore
        $this->client->request('GET', '/');
        $this->assertResponseRedirects('http://localhost/login');
    }
}

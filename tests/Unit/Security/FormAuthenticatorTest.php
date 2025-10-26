<?php

namespace App\Tests\Unit\Security;

use App\Security\FormAuthenticator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class FormAuthenticatorTest extends TestCase
{
    private FormAuthenticator $authenticator;

    /**
     * @var UrlGeneratorInterface&MockObject
     */
    private UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->authenticator = new FormAuthenticator($this->urlGenerator);
    }

    public function testAuthenticateCreatesProperPassport(): void
    {
        $email = 'test@example.com';
        $password = 'password123';
        $csrfToken = 'csrf_token_value';

        // Use a real session for this test
        $session = new Session(new MockArraySessionStorage());

        $request = Request::create('/login', 'POST', [], [], [], [], json_encode([
            'email' => $email,
            'password' => $password,
            '_csrf_token' => $csrfToken,
        ]));
        $request->setSession($session);
        $request->headers->set('Content-Type', 'application/json');

        $passport = $this->authenticator->authenticate($request);

        // Check UserBadge
        $userBadge = $passport->getBadge(UserBadge::class);
        $this->assertInstanceOf(UserBadge::class, $userBadge);
        $this->assertSame($email, $userBadge->getUserIdentifier());

        // Check PasswordCredentials
        $credentials = $passport->getBadge(PasswordCredentials::class);
        $this->assertInstanceOf(PasswordCredentials::class, $credentials);

        // Check CSRF Token
        $csrfBadge = $passport->getBadge(CsrfTokenBadge::class);
        $this->assertInstanceOf(CsrfTokenBadge::class, $csrfBadge);

        // Check last username was set
        $this->assertSame($email, $session->get(SecurityRequestAttributes::LAST_USERNAME));
    }

    public function testOnAuthenticationSuccessRedirectsToAppReact(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);

        $token = $this->createMock(TokenInterface::class);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with('app_react')
            ->willReturn('/');

        $response = $this->authenticator->onAuthenticationSuccess(
            $request,
            $token,
            'main'
        );

        // Assert it returns a RedirectResponse
        $this->assertInstanceOf(RedirectResponse::class, $response);

        // Cast to RedirectResponse to access getTargetUrl()
        $this->assertSame('/', $response->getTargetUrl());
    }

    public function testGetLoginUrl(): void
    {
        $loginUrl = '/login';

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with(FormAuthenticator::LOGIN_ROUTE)
            ->willReturn($loginUrl);

        $request = new Request();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->authenticator);
        $method = $reflection->getMethod('getLoginUrl');

        $result = $method->invoke($this->authenticator, $request);

        $this->assertSame($loginUrl, $result);
    }

    public function testAuthenticateWithEmptyPassword(): void
    {
        $session = new Session(new MockArraySessionStorage());

        $request = Request::create('/login', 'POST', [], [], [], [], json_encode([
            'email' => 'test@example.com',
            'password' => '',
            '_csrf_token' => 'csrf_token_value',
        ]));
        $request->setSession($session);
        $request->headers->set('Content-Type', 'application/json');

        $passport = $this->authenticator->authenticate($request);

        $credentials = $passport->getBadge(PasswordCredentials::class);
        $this->assertInstanceOf(PasswordCredentials::class, $credentials);
    }

    public function testAuthenticateStoresLastUsernameInSession(): void
    {
        $email = 'test@example.com';
        $session = new Session(new MockArraySessionStorage());

        $request = Request::create('/login', 'POST', [], [], [], [], json_encode([
            'email' => $email,
            'password' => 'password123',
            '_csrf_token' => 'csrf_token_value',
        ]));
        $request->setSession($session);
        $request->headers->set('Content-Type', 'application/json');

        $this->authenticator->authenticate($request);

        $this->assertSame($email, $session->get(SecurityRequestAttributes::LAST_USERNAME));
    }

    public function testAuthenticateWithWhitespaceInEmail(): void
    {
        $email = '  test@example.com  ';
        $session = new Session(new MockArraySessionStorage());

        $request = Request::create('/login', 'POST', [], [], [], [], json_encode([
            'email' => $email,
            'password' => 'password123',
            '_csrf_token' => 'csrf_token_value',
        ]));
        $request->setSession($session);
        $request->headers->set('Content-Type', 'application/json');

        $passport = $this->authenticator->authenticate($request);

        $userBadge = $passport->getBadge(UserBadge::class);
        $this->assertSame($email, $userBadge->getUserIdentifier());
    }

    public function testAuthenticateWithLongEmail(): void
    {
        $email = str_repeat('a', 170).'@example.com';
        $session = new Session(new MockArraySessionStorage());

        $request = Request::create('/login', 'POST', [], [], [], [], json_encode([
            'email' => $email,
            'password' => 'password123',
            '_csrf_token' => 'csrf_token_value',
        ]));
        $request->setSession($session);
        $request->headers->set('Content-Type', 'application/json');

        $passport = $this->authenticator->authenticate($request);

        $userBadge = $passport->getBadge(UserBadge::class);
        $this->assertSame($email, $userBadge->getUserIdentifier());
    }

    public function testAuthenticateWithSpecialCharactersInPassword(): void
    {
        $password = '!@#$%^&*()_+-=[]{}|;\':",./<>?';
        $session = new Session(new MockArraySessionStorage());

        $request = Request::create('/login', 'POST', [], [], [], [], json_encode([
            'email' => 'test@example.com',
            'password' => $password,
            '_csrf_token' => 'csrf_token_value',
        ]));
        $request->setSession($session);
        $request->headers->set('Content-Type', 'application/json');

        $passport = $this->authenticator->authenticate($request);

        $credentials = $passport->getBadge(PasswordCredentials::class);
        $this->assertInstanceOf(PasswordCredentials::class, $credentials);
    }
}

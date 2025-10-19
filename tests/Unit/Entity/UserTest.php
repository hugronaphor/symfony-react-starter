<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testGettersAndSetters(): void
    {
        // Test email
        $email = 'test@example.com';
        $this->user->setEmail($email);
        $this->assertSame($email, $this->user->getEmail());

        // Test password
        $password = 'hashed_password_123';
        $this->user->setPassword($password);
        $this->assertSame($password, $this->user->getPassword());

        // Test roles
        $roles = ['ROLE_USER', 'ROLE_ADMIN'];
        $this->user->setRoles($roles);
        $this->assertSame($roles, $this->user->getRoles());
    }

    public function testGetUserIdentifierReturnsEmail(): void
    {
        $email = 'user@example.com';
        $this->user->setEmail($email);

        $this->assertSame($email, $this->user->getUserIdentifier());
    }

    public function testGetRolesAlwaysIncludesRoleUser(): void
    {
        // Test with no roles set
        $this->user->setRoles([]);
        $this->assertContains('ROLE_USER', $this->user->getRoles());

        // Test with other roles
        $this->user->setRoles(['ROLE_ADMIN']);
        $roles = $this->user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);

        // Test with duplicate ROLE_USER
        $this->user->setRoles(['ROLE_USER', 'ROLE_ADMIN', 'ROLE_USER']);
        $roles = $this->user->getRoles();
        $this->assertCount(2, $roles);
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
    }

    public function testSerializeHashesPasswordWithCrc32c(): void
    {
        $originalPassword = 'hashed_password_value';
        $this->user->setEmail('test@example.com');
        $this->user->setPassword($originalPassword);

        // Serialize the user
        $serialized = serialize($this->user);

        // Unserialize to check the password was hashed
        $unserializedUser = unserialize($serialized);

        // The password should now be a CRC32C hash
        $expectedHash = hash('crc32c', $originalPassword);
        $this->assertSame($expectedHash, $unserializedUser->getPassword());
    }

    public function testIdIsNullByDefault(): void
    {
        $this->assertNull($this->user->getId());
    }

    public function testEmailIsEmptyByDefault(): void
    {
        $this->assertEmpty($this->user->getEmail());
    }

    public function testPasswordIsEmptyByDefault(): void
    {
        $this->assertEmpty($this->user->getPassword());
    }

    public function testRolesIsEmptyArrayByDefault(): void
    {
        $user = new User();
        // Even though roles is empty array, getRoles() should return ['ROLE_USER']
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
    }
}

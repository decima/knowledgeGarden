<?php

namespace App\Services\Configuration;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotEqualTo;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Validator\Constraints\When;
use Symfony\Contracts\Service\Attribute\Required;

class User implements UserInterface, PasswordAuthenticatedUserInterface
{

    #[NotEqualTo("admin")]
    public string $username;
    public string $password = "";

    #[Ignore]
    #[When(
        expression: 'this.password !== "" or this.password !== ""',
        constraints: [
            new Length(min: 8)
        ],
    )]
    public ?string $clearPassword = null;

    public array $permissions = [];

    #[Ignore]
    public function getRoles(): array
    {
        return ["ROLE_READ", ...$this->permissions];
    }

    public function eraseCredentials(): void
    {
        $this->clearPassword = null;
    }


    #[Ignore]
    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }
}
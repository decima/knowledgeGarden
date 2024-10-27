<?php

namespace App\Services\Configuration;

use App\Services\Configuration\ConfigurationManager;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{

    public function __construct(private Configuration $configuration)
    {
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        $username = $user->getUserIdentifier();

        return $this->loadUserByIdentifier($username);
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        foreach ($this->configuration->getUsers() as $user) {
            if (strtolower($user->username) === strtolower($identifier)) {
                return $user;
            }
        }
        throw new UserNotFoundException(
            sprintf('Username "%s" does not exist.', $identifier)
        );
    }

    public function storeUser(User $user)
    {
        $this->configuration->addUser($user);
    }
}
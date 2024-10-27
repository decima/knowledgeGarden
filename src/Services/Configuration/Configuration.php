<?php

namespace App\Services\Configuration;

use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotEqualTo;
use Symfony\Component\Validator\Constraints\Valid;

class Configuration
{
    #[Ignore]
    public bool $inMemory = true;

    #[Ignore]
    public ?string $stateAsString = null;

    #[NotBlank]
    public string $title = 'garden';


    public string $copyright = "";

    /**
     * @var User[]
     */
    #[Valid]
    private array $users = [];

    public function addUser(User $user)
    {
        foreach ($this->users as $storedUser) {
            if ($user->username === $storedUser->username) {
                return;
            }
        }

        $this->users[] = $user;
    }


    /**
     * @return User[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param User[] $users
     * @return void
     */
    public function setUsers(array $users)
    {
        $this->users = $users;
    }
}
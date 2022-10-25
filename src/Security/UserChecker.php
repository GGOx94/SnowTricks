<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user)
    {
        if ($user->isBanned()) {
            throw new CustomUserMessageAuthenticationException("Votre compte a été banni par un administrateur.");
        }
        if (!$user->isVerified()) {
            throw new CustomUserMessageAuthenticationException("Votre compte n'est pas encore vérifié, regardez votre boîte mail !");
        }
    }

    public function checkPostAuth(UserInterface $user)
    {

    }
}
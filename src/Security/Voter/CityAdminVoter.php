<?php

namespace App\Security\Voter;

use App\Entity\City;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class CityAdminVoter extends Voter
{
    public const MANAGE = 'CITY_MANAGE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::MANAGE && $subject instanceof City;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        if (!$user instanceof User) {
            return false;
        }

        // Super admins can manage any city
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        /** @var City $city */
        $city = $subject;

        // City admins can only manage their own city
        return in_array('ROLE_ADMIN_CITY', $user->getRoles(), true)
            && $user->getCity()?->getId() === $city->getId();
    }
}

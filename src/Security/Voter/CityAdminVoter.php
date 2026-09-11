<?php

namespace App\Security\Voter;

use App\Entity\City;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CityAdminVoter extends Voter
{
    public const MANAGE = 'CITY_MANAGE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::MANAGE && $subject instanceof City;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$token->getUser() instanceof User) {
            return false;
        }

        // Super admins can manage any city — routed through the role hierarchy
        // rather than a raw getRoles() check, so a hierarchy change can't desync this.
        if ($this->accessDecisionManager->decide($token, ['ROLE_SUPER_ADMIN'])) {
            return true;
        }

        /** @var User $user */
        $user = $token->getUser();

        /** @var City $city */
        $city = $subject;

        // City admins can only manage their own city
        return $this->accessDecisionManager->decide($token, ['ROLE_ADMIN_CITY'])
            && $user->getCity()?->getId() === $city->getId();
    }
}

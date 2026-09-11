<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Guards city-admin actions on a specific participant. CITY_MANAGE only proves
 * the actor may administer a city — it says nothing about whether the target
 * user actually belongs to that city, so every action that loads a User by a
 * route id must also check this voter on that User.
 */
class ParticipantVoter extends Voter
{
    public const MANAGE = 'PARTICIPANT_MANAGE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::MANAGE === $attribute && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$token->getUser() instanceof User) {
            return false;
        }

        if ($this->accessDecisionManager->decide($token, ['ROLE_SUPER_ADMIN'])) {
            return true;
        }

        /** @var User $actor */
        $actor = $token->getUser();

        /** @var User $participant */
        $participant = $subject;

        // A city admin may never manage a super-admin or another city-admin account —
        // only plain participants of their own city.
        if ($participant->isSuperAdmin() || $participant->isAdminCity()) {
            return false;
        }

        return $this->accessDecisionManager->decide($token, ['ROLE_ADMIN_CITY'])
            && null !== $actor->getCity()
            && $actor->getCity()->getId() === $participant->getCity()?->getId();
    }
}

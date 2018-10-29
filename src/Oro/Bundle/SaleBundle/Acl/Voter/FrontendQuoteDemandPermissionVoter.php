<?php

namespace Oro\Bundle\SaleBundle\Acl\Voter;

use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Checks if given QuoteDemand contains valid visitor or user has access to related quote.
 */
class FrontendQuoteDemandPermissionVoter extends Voter
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        return $attribute === BasicPermissionMap::PERMISSION_VIEW && $subject instanceof QuoteDemand;
    }

    /**
     * {@inheritdoc}
     * @var QuoteDemand $subject
     * @var AnonymousCustomerUserToken $token
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if ($this->isApplicable()) {
            return $subject->getVisitor() && $subject->getVisitor() === $token->getVisitor();
        }

        return $subject->getQuote() &&
            $this->authorizationChecker->isGranted('oro_sale_quote_frontend_view', $subject->getQuote());
    }

    /**
     * @return bool
     */
    private function isApplicable(): bool
    {
        return $this->tokenStorage->getToken() instanceof AnonymousCustomerUserToken;
    }
}

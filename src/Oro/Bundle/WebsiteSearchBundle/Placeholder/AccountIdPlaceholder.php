<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

use Oro\Bundle\AccountBundle\Entity\AccountUser;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AccountIdPlaceholder extends AbstractPlaceholder
{
    const NAME = 'ACCOUNT_ID';

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function getPlaceholder()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValue()
    {
        $token = $this->tokenStorage->getToken();

        if ($token && ($user = $token->getUser()) instanceof AccountUser) {
            return $user->getId();
        }

        return null;
    }
}

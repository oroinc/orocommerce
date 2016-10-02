<?php

namespace Oro\Bundle\FrontendNavigationBundle\Provider;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\NavigationBundle\Model\OwnershipProviderInterface;

class AccountOwnershipProvider implements OwnershipProviderInterface
{
    const TYPE = 'account';

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return self::TYPE;
    }

    /**
     * @return integer|null
     */
    public function getId()
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return null;
        }
        $user = $token->getUser();
        if (!$user instanceof AccountUser || $user === null) {
            return null;
        }

        return $user->getAccount();
    }
}

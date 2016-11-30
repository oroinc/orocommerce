<?php

namespace Oro\Bundle\CommerceMenuBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\NavigationBundle\Menu\Provider\AbstractOwnershipProvider;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;

class AccountOwnershipProvider extends AbstractOwnershipProvider
{
    const TYPE = 'account';

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /**
     * @param ManagerRegistry       $managerRegistry
     * @param string                $entityClass
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(ManagerRegistry $managerRegistry, $entityClass, TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
        parent::__construct($managerRegistry, $entityClass);
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
        if (!$user instanceof AccountUser) {
            return null;
        }

        return $user->getAccount()->getId();
    }
}

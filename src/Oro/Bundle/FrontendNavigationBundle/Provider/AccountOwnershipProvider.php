<?php

namespace Oro\Bundle\FrontendNavigationBundle\Provider;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\NavigationBundle\Model\AbstractOwnershipProvider;
use Oro\Bundle\AccountBundle\Entity\AccountUser;

class AccountOwnershipProvider extends AbstractOwnershipProvider
{
    const TYPE = 'account';

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /**
     * @param EntityRepository      $repository
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(EntityRepository $repository, TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
        parent::__construct($repository);
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

        return $user->getAccount();
    }
}

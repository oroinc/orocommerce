<?php

namespace OroB2B\Bundle\AccountBundle\Acl\Voter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Security\AccountUserRoleProvider;

class AccountUserRoleVoter extends AbstractEntityVoter
{
    const ATTRIBUTE_DELETE = 'DELETE';
    const ATTRIBUTE_FRONTEND_ACCOUNT_ROLE_UPDATE = 'FRONTEND_ACCOUNT_ROLE_UPDATE';
    const ATTRIBUTE_FRONTEND_ACCOUNT_ROLE_VIEW = 'FRONTEND_ACCOUNT_ROLE_VIEW';

    const VIEW = 'view';
    const UPDATE = 'update';

    /**
     * @var array
     */
    protected $supportedAttributes = [
        self::ATTRIBUTE_DELETE,
        self::ATTRIBUTE_FRONTEND_ACCOUNT_ROLE_UPDATE,
        self::ATTRIBUTE_FRONTEND_ACCOUNT_ROLE_VIEW
    ];

    /**
     * @var AccountUserRole
     */
    protected $object;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var AccountUserRoleProvider
     */
    protected $accountUserRoleProvider;

    /**
     * @param DoctrineHelper     $doctrineHelper
     * @param ContainerInterface $container
     */
    public function __construct(DoctrineHelper $doctrineHelper, ContainerInterface $container)
    {
        parent::__construct($doctrineHelper);
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        $this->object = $object;

        return parent::vote($token, $object, $attributes);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        if (!$this->object instanceof AccountUserRole) {
            return self::ACCESS_ABSTAIN;
        }

        switch ($attribute) {
            case static::ATTRIBUTE_DELETE:
                return $this->getPermissionForDelete();
            case static::ATTRIBUTE_FRONTEND_ACCOUNT_ROLE_VIEW:
                return $this->getPermissionForAccountRole(self::VIEW);
            case static::ATTRIBUTE_FRONTEND_ACCOUNT_ROLE_UPDATE:
                return $this->getPermissionForAccountRole(self::UPDATE);
            default:
                return self::ACCESS_ABSTAIN;
        }
    }

    /**
     * @return int
     */
    protected function getPermissionForDelete()
    {
        /** @var AccountUserRoleRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository('OroB2BAccountBundle:AccountUserRole');

        $isDefaultForWebsite = $repository->isDefaultForWebsite($this->object);
        $hasAssignedUsers = $repository->hasAssignedUsers($this->object);

        if ($isDefaultForWebsite || $hasAssignedUsers) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }

    /**
     * @param string $type
     * @return int
     */
    protected function getPermissionForAccountRole($type)
    {
        /* @var $user AccountUser */
        $user = $this->getAccountUserRoleProvider()->getLoggedUser();

        /** @var Account $account */
        $account = $this->object->getAccount();

        if (!$user instanceof AccountUser) {
            return self::ACCESS_ABSTAIN;
        }

        switch ($type) {
            case self::VIEW:
                $isGranted = $this->getAccountUserRoleProvider()->isGrantedViewAccountUserRole();
                break;
            case self::UPDATE:
                $isGranted = $this->getAccountUserRoleProvider()->isGrantedUpdateAccountUserRole();
                break;
            default:
                $isGranted = false;
        }

        if ($isGranted && (!$account || $account->getId() === $user->getAccount()->getId())) {
            return self::ACCESS_GRANTED;
        }

        return self::ACCESS_ABSTAIN;
    }

    /**
     * @return AccountUserRoleProvider
     */
    protected function getAccountUserRoleProvider()
    {
        if (!$this->accountUserRoleProvider) {
            $this->accountUserRoleProvider = $this->container
                ->get('orob2b_account.security.account_user_role_provider');
        }

        return $this->accountUserRoleProvider;
    }
}

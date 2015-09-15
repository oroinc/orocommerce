<?php

namespace OroB2B\Bundle\AccountBundle\Acl\Voter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Account;

class AccountUserRoleVoter extends AbstractEntityVoter
{
    const ATTRIBUTE_DELETE = 'DELETE';
    const ATTRIBUTE_FRONTEND_ACCOUNT_ROLE_UPDATE = 'FRONTEND_ACCOUNT_ROLE_UPDATE';
    const ATTRIBUTE_FRONTEND_ACCOUNT_ROLE_VIEW = 'FRONTEND_ACCOUNT_ROLE_VIEW';
    const ATTRIBUTE_FRONTEND_ACCOUNT_ROLE_DELETE = 'FRONTEND_ACCOUNT_ROLE_DELETE';

    const VIEW = 'view';
    const UPDATE = 'update';

    /**
     * @var array
     */
    protected $supportedAttributes = [
        self::ATTRIBUTE_DELETE,
        self::ATTRIBUTE_FRONTEND_ACCOUNT_ROLE_UPDATE,
        self::ATTRIBUTE_FRONTEND_ACCOUNT_ROLE_VIEW,
        self::ATTRIBUTE_FRONTEND_ACCOUNT_ROLE_DELETE,
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
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
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
            case static::ATTRIBUTE_FRONTEND_ACCOUNT_ROLE_DELETE:
                return $this->getFrontendPermissionForDelete();
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
        $user = $this->getLoggedUser();

        /** @var Account $account */
        $account = $this->object->getAccount();

        if (!$user instanceof AccountUser) {
            return self::ACCESS_ABSTAIN;
        }

        $isGranted = false;

        switch ($type) {
            case self::VIEW:
                $isGranted = $this->isGrantedViewAccountUserRole();
                break;
            case self::UPDATE:
                $isGranted = $this->isGrantedUpdateAccountUserRole();
                break;
        }

        if ($isGranted && (!$account || $account->getId() === $user->getAccount()->getId())) {
            return self::ACCESS_GRANTED;
        }

        return self::ACCESS_ABSTAIN;
    }

    /**
     * @return SecurityFacade
     */
    protected function getSecurityFacade()
    {
        return $this->container->get('oro_security.security_facade');
    }

    /**
     * @return AccountUser|null
     */
    protected function getLoggedUser()
    {
        return $this->getSecurityFacade()->getLoggedUser();
    }

    /**
     * @return boolean
     */
    protected function isGrantedUpdateAccountUserRole()
    {
        return $this->isGrantedAccountUserRole(BasicPermissionMap::PERMISSION_EDIT);
    }

    /**
     * @return boolean
     */
    protected function isGrantedViewAccountUserRole()
    {
        return $this->isGrantedAccountUserRole(BasicPermissionMap::PERMISSION_VIEW);
    }

    /**
     * @param $permissionMap
     * @return bool
     */
    protected function isGrantedAccountUserRole($permissionMap)
    {
        $descriptor = sprintf('entity:%s@%s', AccountUser::SECURITY_GROUP, $this->className);
        if (!$this->getSecurityFacade()->isGranted($permissionMap, $descriptor)) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     * @return int
     */
    protected function getFrontendPermissionForDelete()
    {
        if ($this->object->isPredefined()) {
            return self::ACCESS_DENIED;
        }

        return $this->isGrantedDeleteAccountUserRole($this->object) ? self::ACCESS_GRANTED : self::ACCESS_DENIED;
    }

    /**
     * @param $object
     * @return bool
     */
    protected function isGrantedDeleteAccountUserRole($object)
    {
        return $this->getSecurityFacade()->isGranted(BasicPermissionMap::PERMISSION_DELETE, $object);
    }
}

<?php

namespace Oro\Bundle\CustomerBundle\Acl\Voter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerUserRoleRepository;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

class CustomerUserRoleVoter extends AbstractEntityVoter
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
     * @var CustomerUserRole
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
        if (!$this->object instanceof CustomerUserRole) {
            return self::ACCESS_ABSTAIN;
        }

        switch ($attribute) {
            case static::ATTRIBUTE_DELETE:
                return $this->getPermissionForDelete();
            case static::ATTRIBUTE_FRONTEND_ACCOUNT_ROLE_VIEW:
                return $this->getPermissionForCustomerRole(self::VIEW);
            case static::ATTRIBUTE_FRONTEND_ACCOUNT_ROLE_UPDATE:
                return $this->getPermissionForCustomerRole(self::UPDATE);
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
        /** @var CustomerUserRoleRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository('OroCustomerBundle:CustomerUserRole');

        $isDefaultForWebsite = $repository->isDefaultForWebsite($this->object);
        $hasAssignedUsers = $repository->hasAssignedUsers($this->object);

        if ($isDefaultForWebsite || $hasAssignedUsers) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_GRANTED;
    }

    /**
     * @param string $type
     * @return int
     */
    protected function getPermissionForCustomerRole($type)
    {
        /* @var $user CustomerUser */
        $user = $this->getLoggedUser();

        if (!$user instanceof CustomerUser) {
            return self::ACCESS_DENIED;
        }

        $isGranted = false;

        switch ($type) {
            case self::VIEW:
                $isGranted = $this->isGrantedViewCustomerUserRole();
                break;
            case self::UPDATE:
                $isGranted = $this->isGrantedUpdateCustomerUserRole();
                break;
        }

        return $isGranted ? self::ACCESS_GRANTED : self::ACCESS_DENIED;
    }

    /**
     * @return SecurityFacade
     */
    protected function getSecurityFacade()
    {
        return $this->container->get('oro_security.security_facade');
    }

    /**
     * @return CustomerUser|null
     */
    protected function getLoggedUser()
    {
        return $this->getSecurityFacade()->getLoggedUser();
    }

    /**
     * @return boolean
     */
    protected function isGrantedUpdateCustomerUserRole()
    {
        if ($this->object->isPredefined()) {
            return true;
        }

        return $this->getSecurityFacade()->isGranted(BasicPermissionMap::PERMISSION_EDIT, $this->object);
    }

    /**
     * @return boolean
     */
    protected function isGrantedViewCustomerUserRole()
    {
        if ($this->object->isPredefined()) {
            return true;
        }

        return $this->getSecurityFacade()->isGranted(BasicPermissionMap::PERMISSION_VIEW, $this->object);
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

        return $this->isGrantedDeleteCustomerUserRole($this->object) ? self::ACCESS_GRANTED : self::ACCESS_DENIED;
    }

    /**
     * @param $object
     * @return bool
     */
    protected function isGrantedDeleteCustomerUserRole($object)
    {
        return $this->getSecurityFacade()->isGranted(BasicPermissionMap::PERMISSION_DELETE, $object);
    }
}

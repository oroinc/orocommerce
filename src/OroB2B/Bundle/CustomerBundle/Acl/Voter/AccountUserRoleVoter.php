<?php

namespace OroB2B\Bundle\CustomerBundle\Acl\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole;
use OroB2B\Bundle\CustomerBundle\Entity\Repository\AccountUserRoleRepository;

class AccountUserRoleVoter extends AbstractEntityVoter
{
    const ATTRIBUTE_DELETE = 'DELETE';

    /**
     * @var array
     */
    protected $supportedAttributes = [
        self::ATTRIBUTE_DELETE
    ];

    /**
     * @var AccountUserRole
     */
    protected $object;

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
        /** @var AccountUserRoleRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository('OroB2BCustomerBundle:AccountUserRole');

        $isDefaultForWebsite = $repository->isDefaultForWebsite($this->object);
        $hasAssignedUsers = $repository->hasAssignedUsers($this->object);

        if ($isDefaultForWebsite || $hasAssignedUsers) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }
}

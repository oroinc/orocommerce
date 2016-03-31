<?php

namespace OroB2B\Bundle\AccountBundle\Layout\DataProvider;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Helper\AccountUserRolePrivilegesHelper;

class FrontendAccountUserRolePrivilegesDataProvider implements DataProviderInterface
{
    /** @var AccountUserRolePrivilegesHelper */
    protected $privilegesHelper;

    /**
     * @param AccountUserRolePrivilegesHelper $privilegesHelper
     */
    public function __construct(AccountUserRolePrivilegesHelper $privilegesHelper)
    {
        $this->privilegesHelper = $privilegesHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        if (!$context->data()->has('entity')) {
            return null;
        }

        $role = $context->data()->get('entity');

        if (!$role instanceof AccountUserRole) {
            return null;
        }

        return $this->privilegesHelper->collect($role);
    }
}

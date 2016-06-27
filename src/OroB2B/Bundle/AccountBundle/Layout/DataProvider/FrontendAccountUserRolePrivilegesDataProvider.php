<?php

namespace OroB2B\Bundle\AccountBundle\Layout\DataProvider;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;

class FrontendAccountUserRolePrivilegesDataProvider implements DataProviderInterface
{
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

        return [];
    }
}

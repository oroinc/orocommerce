<?php

namespace Oro\Bundle\SaleBundle\Migrations\Data\ORM;

use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractLoadAclData;

/**
 * Loads quote workflows ACL data
 */
class LoadWorkflowAclData extends AbstractLoadAclData
{
    /**
     * {@inheritDoc}
     */
    protected function getDataPath()
    {
        return '@OroSaleBundle/Migrations/Data/ORM/data/workflow_roles.yml';
    }
}

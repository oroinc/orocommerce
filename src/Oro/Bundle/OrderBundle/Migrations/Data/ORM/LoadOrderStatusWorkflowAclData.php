<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\ORM;

use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractLoadAclData;

/**
 * Loads ACL for "order_processing_flow" workflow.
 */
class LoadOrderStatusWorkflowAclData extends AbstractLoadAclData
{
    #[\Override]
    protected function getDataPath(): string
    {
        return '@OroOrderBundle/Migrations/Data/ORM/data/order_processing_workflow_acl.yml';
    }
}

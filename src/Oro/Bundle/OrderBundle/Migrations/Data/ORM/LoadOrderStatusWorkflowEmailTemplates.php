<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\ORM;

use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractEmailFixture;

/**
 * Loads email templates for "order_processing_flow" workflow.
 */
class LoadOrderStatusWorkflowEmailTemplates extends AbstractEmailFixture
{
    #[\Override]
    public function getEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroOrderBundle/Migrations/Data/ORM/data/emails/order_processing_workflow');
    }
}

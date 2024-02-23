<?php

namespace Oro\Bundle\RFPBundle\Migrations\Data\ORM;

use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;

/**
 * Update only not customized `request_create_confirmation` email template with
 * {@see RequestProductKitItemLineItem} collection.
 */
class AddKitItemLineItemsToRequestCreateConfirmationEmailTemplate extends ConvertRFQRequestConfirmationEmail
{
    public function getEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroRFPBundle/Migrations/Data/ORM/data/emails/v1_1');
    }

    public function getDependencies(): array
    {
        return [ConvertRFQRequestConfirmationEmail::class];
    }

    public function getPreviousEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroRFPBundle/Migrations/Data/ORM/data/emails/request');
    }
}

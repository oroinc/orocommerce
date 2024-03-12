<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Data\ORM;

use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;

/**
 * Update only not customized `order_confirmation_email` email template with
 * {@see OrderProductKitItemLineItem} collection.
 */
class AddKitItemLineItemsToOrderConfirmationEmailTemplate extends UpdateOrderConfirmationEmailTemplate
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [ConvertOrderConfirmationEmail::class];
    }

    /**
     * {@inheritDoc}
     */
    public function getEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroCheckoutBundle/Migrations/Data/ORM/data/emails/v1_2');
    }

    /**
     * {@inheritDoc}
     */
    public function getPreviousEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroCheckoutBundle/Migrations/Data/ORM/data/emails/order');
    }
}

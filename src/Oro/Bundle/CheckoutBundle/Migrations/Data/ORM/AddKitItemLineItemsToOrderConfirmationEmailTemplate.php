<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Data\ORM;

use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;

/**
 * Update only not customized `order_confirmation_email` email template with
 * {@see OrderProductKitItemLineItem} collection.
 */
class AddKitItemLineItemsToOrderConfirmationEmailTemplate extends UpdateOrderConfirmationEmailTemplate
{
    public function getDependencies()
    {
        return [ConvertOrderConfirmationEmail::class];
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailsDir()
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroCheckoutBundle/Migrations/Data/ORM/data/emails/v1_2');
    }

    /**
     * Return path to old email templates
     *
     * @return string
     */
    public function getPreviousEmailsDir()
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroCheckoutBundle/Migrations/Data/ORM/data/emails/order');
    }
}

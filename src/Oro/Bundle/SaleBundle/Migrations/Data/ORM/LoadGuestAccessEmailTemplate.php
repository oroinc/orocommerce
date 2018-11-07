<?php

namespace Oro\Bundle\SaleBundle\Migrations\Data\ORM;

use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractEmailFixture;

/**
 * Adds the email template which can used to send to the customer user the guest link to the quote.
 */
class LoadGuestAccessEmailTemplate extends AbstractEmailFixture
{
    /**
     * {@inheritdoc}
     */
    public function getEmailsDir()
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroSaleBundle/Migrations/Data/ORM/guest_access_email');
    }
}

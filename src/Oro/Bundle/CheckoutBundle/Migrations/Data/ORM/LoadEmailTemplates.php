<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Data\ORM;

use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractHashEmailMigration;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

/**
 * Load new templates if not present, update existing as configured by {@see self::getEmailHashesToUpdate}.
 */
class LoadEmailTemplates extends AbstractHashEmailMigration implements VersionedFixtureInterface
{
    public function getEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroCheckoutBundle/Migrations/Data/ORM/data/emails/order');
    }

    public function getVersion(): string
    {
        return '1.4';
    }

    protected function getEmailHashesToUpdate(): array
    {
        return [
            'checkout_registration_confirmation' => [
                '61d0aa78a03cff496e373d85f3f3bfce', // 1.0
                '86c875b20d9f63dbb998be38b92a4b15', // 1.1
            ],
            'order_confirmation_email' => [
                'ca60fc6a8ddd1b6c8d880ff0487c9b6e', // 1.0
                '3c6be02cf2a212034ee53f5ec1e2558a', // 1.1
                '36b16614d479d6f41bf7a62720267d60', // 1.2
            ],
            'checkout_customer_user_reset_password' => [
                'd5eac9230ad16940519a2cd1e0bfa88e', // 1.0
                'f8037a44b89505241b3907df7f7e1e61', // 1.1
                'd39360c4af46a547a1246007744186d3', // 1.3
            ],
        ];
    }
}

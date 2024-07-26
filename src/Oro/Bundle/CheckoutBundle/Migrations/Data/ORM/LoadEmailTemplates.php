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
        return '1.5';
    }

    protected function getEmailHashesToUpdate(): array
    {
        return [
            'checkout_registration_confirmation' => [
                '61d0aa78a03cff496e373d85f3f3bfce', // 1.0
                '86c875b20d9f63dbb998be38b92a4b15', // 1.1
                '86c875b20d9f63dbb998be38b92a4b15', // 1.2
                '86c875b20d9f63dbb998be38b92a4b15', // 1.3
                '86c875b20d9f63dbb998be38b92a4b15', // 1.4
                'a8952d5890f3157ef31f76ea69f25def', // 1.5
            ],
            'order_confirmation_email' => [
                'ca60fc6a8ddd1b6c8d880ff0487c9b6e', // 1.0
                '3c6be02cf2a212034ee53f5ec1e2558a', // 1.1
                '36b16614d479d6f41bf7a62720267d60', // 1.2
                '18f8d8a97d63566558eb443746bedacd', // 1.3
                '53597bf198ade0df35b5f20018a11a31', // 1.4
                '6ab853d9b721eb0132807e1a5bae0e55', // 1.5
            ],
            'checkout_customer_user_reset_password' => [
                'd5eac9230ad16940519a2cd1e0bfa88e', // 1.0
                'f8037a44b89505241b3907df7f7e1e61', // 1.1
                'f8037a44b89505241b3907df7f7e1e61', // 1.2
                'f8037a44b89505241b3907df7f7e1e61', // 1.3
                'f8037a44b89505241b3907df7f7e1e61', // 1.4
                '1b5d572d75046dc5108b8eeec7ce16e2', // 1.5
            ],
        ];
    }
}

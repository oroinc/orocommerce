<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\UserBundle\Tests\Functional\Api\DataFixtures\AbstractLoadUserData;

class LoadUserData extends AbstractLoadUserData
{
    public const USER_NAME_CATALOG_MANAGER = 'system_user_catalog_manager';
    public const USER_PASSWORD_CATALOG_MANAGER = 'system_user_cm_api_key';
    public const ROLE_CATALOG_MANAGER = 'ROLE_CATALOG_MANAGER';

    #[\Override]
    protected function getUsersData(): array
    {
        return [
            [
                'username' => self::USER_NAME_CATALOG_MANAGER,
                'email' => 'system_user_cm@example.com',
                'firstName' => 'Giffard',
                'lastName' => 'Gray',
                'plainPassword' => self::USER_PASSWORD_CATALOG_MANAGER,
                'reference' => 'oro_user:user:system_user_cm',
                'enabled' => true,
                'role' => self::ROLE_CATALOG_MANAGER,
                'group' => 'Administrators',
            ],
        ];
    }
}

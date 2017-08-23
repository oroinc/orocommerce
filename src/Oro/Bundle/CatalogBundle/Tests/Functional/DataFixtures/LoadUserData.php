<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\UserBundle\Tests\Functional\API\DataFixtures\AbstractLoadUserData;

class LoadUserData extends AbstractLoadUserData
{
    const USER_NAME_CATALOG_MANAGER = 'system_user_catalog_manager';
    const USER_PASSWORD_CATALOG_MANAGER = 'system_user_cm_api_key';

    /**
     * {@inheritdoc}
     */
    protected function getUsersData()
    {
        return [
            [
                'username' => 'system_user_catalog_manager',
                'email' => 'system_user_cm@example.com',
                'firstName' => 'Giffard',
                'lastName' => 'Gray',
                'plainPassword' => 'system_user_cm_api_key',
                'apiKey' => 'system_user_cm_api_key',
                'reference' => 'oro_user:user:system_user_cm',
                'enabled' => true,
                'role' => 'ROLE_CATALOG_MANAGER',
                'group' => 'Administrators',
            ],
        ];
    }
}

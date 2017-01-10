<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\AbstractLoadACLData;

class LoadCheckoutUserACLData extends AbstractLoadACLData
{
    const ROLE_BASIC = 'USER_ROLE_BASIC';
    const ROLE_LOCAL = 'USER_ROLE_LOCAL';
    const ROLE_LOCAL_VIEW_ONLY = 'USER_ROLE_LOCAL_VIEW_ONLY';
    const ROLE_DEEP_VIEW_ONLY = 'USER_ROLE_DEEP_VIEW_ONLY';
    const ROLE_DEEP = 'USER_ROLE_DEEP';

    /**
     * @return string
     */
    protected function getAclResourceClassName()
    {
        return Checkout::class;
    }

    /**
     * @return array
     */
    protected function getSupportedRoles()
    {
        return [
            self::ROLE_BASIC,
            self::ROLE_LOCAL,
            self::ROLE_LOCAL_VIEW_ONLY,
            self::ROLE_DEEP,
            self::ROLE_DEEP_VIEW_ONLY,
        ];
    }
}

<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;

class LoadAccountUserACLData extends AbstractLoadACLData
{
    /**
     * @return string
     */
    protected function getAclResourceClassName()
    {
        return AccountUser::class;
    }

    /**
     * @return array
     */
    protected function getSupportedRoles()
    {
        return [
            self::ROLE_LOCAL,
            self::ROLE_LOCAL_VIEW_ONLY,
            self::ROLE_DEEP
        ];
    }
}

<?php

namespace Oro\Bundle\AccountBundle\Entity\Repository;

use Oro\Bundle\NavigationBundle\Entity\Repository\PinbarTabRepository as BasePinbarTabRepository;

class PinbarTabRepository extends BasePinbarTabRepository
{
    /**
     * {@inheritdoc}
     */
    protected function getNavigationItemClassName()
    {
        return 'Oro\Bundle\AccountBundle\Entity\NavigationItem';
    }
}

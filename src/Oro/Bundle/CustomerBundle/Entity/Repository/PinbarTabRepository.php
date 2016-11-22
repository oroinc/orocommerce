<?php

namespace Oro\Bundle\CustomerBundle\Entity\Repository;

use Oro\Bundle\NavigationBundle\Entity\Repository\PinbarTabRepository as BasePinbarTabRepository;

class PinbarTabRepository extends BasePinbarTabRepository
{
    /**
     * {@inheritdoc}
     */
    protected function getNavigationItemClassName()
    {
        return 'Oro\Bundle\CustomerBundle\Entity\NavigationItem';
    }
}

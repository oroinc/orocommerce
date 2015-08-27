<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Oro\Bundle\NavigationBundle\Entity\Repository\PinbarTabRepository as BasePinbarTabRepository;

class PinbarTabRepository extends BasePinbarTabRepository
{
    /**
     * {@inheritdoc}
     */
    protected function getNavigationItemClassName()
    {
        return 'OroB2B\Bundle\AccountBundle\Entity\NavigationItem';
    }
}

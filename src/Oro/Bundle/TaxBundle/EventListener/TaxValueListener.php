<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\Manager\TaxValueManager;

/**
 * Clears taxValueManager caches on postRemove
 */
class TaxValueListener
{
    public function __construct(
        private TaxValueManager $taxValueManager
    ) {
    }

    public function postRemove(TaxValue $taxValue, LifecycleEventArgs $event): void
    {
        $this->taxValueManager->clear();
    }
}

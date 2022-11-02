<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\Manager\TaxValueManager;

class TaxValueListener
{
    /** @var TaxValueManager */
    protected $taxValueManager;

    public function __construct(TaxValueManager $taxValueManager)
    {
        $this->taxValueManager = $taxValueManager;
    }

    public function postRemove(TaxValue $taxValue, LifecycleEventArgs $event)
    {
        $this->taxValueManager->clear();
    }
}

<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\Manager\TaxValueManager;

class TaxValueListener
{
    /** @var TaxValueManager */
    protected $taxValueManager;

    /**
     * @param TaxValueManager $taxValueManager
     */
    public function __construct(TaxValueManager $taxValueManager)
    {
        $this->taxValueManager = $taxValueManager;
    }

    /**
     * @param TaxValue $taxValue
     * @param LifecycleEventArgs $event
     */
    public function postRemove(TaxValue $taxValue, LifecycleEventArgs $event)
    {
        $this->taxValueManager->clear();
    }
}

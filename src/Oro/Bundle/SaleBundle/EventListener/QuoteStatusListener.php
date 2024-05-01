<?php

namespace Oro\Bundle\SaleBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\SaleBundle\Entity\Quote;

/**
 * Since some workflows may be disabled or has no "transitions permissions", we will use the default internal status
 * for quotes.
 */
class QuoteStatusListener
{
    public function __construct(private ManagerRegistry $registry)
    {
    }

    public function prePersist(Quote $entity): void
    {
        if (!$entity->getInternalStatus()) {
            $entity->setInternalStatus($this->getInternalStatusForQuote());
        }
    }

    private function getInternalStatusForQuote(): AbstractEnumValue
    {
        $className = ExtendHelper::buildEnumValueClassName(Quote::INTERNAL_STATUS_CODE);
        // By default use 'draft' status.
        return $this->registry->getManager()->find($className, Quote::INTERNAL_STATUS_DRAFT);
    }
}

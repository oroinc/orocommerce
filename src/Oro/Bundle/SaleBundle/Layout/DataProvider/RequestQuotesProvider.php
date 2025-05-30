<?php

namespace Oro\Bundle\SaleBundle\Layout\DataProvider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Provides information about quotes created from RFQ.
 */
class RequestQuotesProvider
{
    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly AclHelper $aclHelper
    ) {
    }

    /**
     * @return Quote[]
     */
    public function getQuotes(Request $request): array
    {
        $qb = $this->doctrineHelper->createQueryBuilder(Quote::class, 'e')
            ->where('e.request = :request')
            ->setParameter('request', $request)
            ->orderBy('e.createdAt');
        $quotes = $this->aclHelper->apply($qb)->getResult();
        if (!$quotes) {
            return [];
        }

        $filteredQuotes = [];
        /** @var Quote $quote */
        foreach ($quotes as $quote) {
            if (!$quote->isAvailableOnFrontend()) {
                continue;
            }
            $filteredQuotes[] = $quote;
        }

        return $filteredQuotes;
    }
}

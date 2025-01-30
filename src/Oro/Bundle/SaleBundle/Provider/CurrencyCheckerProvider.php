<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Provider\RepositoryCurrencyCheckerProviderInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SaleBundle\Entity\Quote;

/**
 * Provides information about Quote entities that are related to currencies.
 */
class CurrencyCheckerProvider implements RepositoryCurrencyCheckerProviderInterface
{
    const ENTITY_LABEL = 'oro.sale.quote.entity_label';

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    #[\Override]
    public function getEntityLabel()
    {
        return self::ENTITY_LABEL;
    }

    #[\Override]
    public function hasRecordsWithRemovingCurrencies(
        array $removingCurrencies,
        ?Organization $organization = null
    ) {
        $quoteRepository = $this->doctrine->getRepository(Quote::class);
        return $quoteRepository->hasRecordsWithRemovingCurrencies($removingCurrencies, $organization);
    }
}

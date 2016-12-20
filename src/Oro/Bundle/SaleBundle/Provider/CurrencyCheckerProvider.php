<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\CurrencyBundle\Provider\RepositoryCurrencyCheckerProviderInterface;

class CurrencyCheckerProvider implements RepositoryCurrencyCheckerProviderInterface
{
    const ENTITY_LABEL = 'oro.sale.quote.entity_label';

    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    public function __construct(RegistryInterface $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @inheritDoc
     */
    public function getEntityLabel()
    {
        return self::ENTITY_LABEL;
    }

    /**
     * @inheritdoc
     */
    public function hasRecordsWithRemovingCurrencies(
        array $removingCurrencies,
        Organization $organization = null
    ) {
        $quoteRepository = $this->doctrine->getRepository('OroSaleBundle:Quote');
        return $quoteRepository->hasRecordsWithRemovingCurrencies($removingCurrencies, $organization);
    }
}

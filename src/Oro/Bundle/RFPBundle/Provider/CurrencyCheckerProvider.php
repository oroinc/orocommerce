<?php

namespace Oro\Bundle\RFPBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Provider\RepositoryCurrencyCheckerProviderInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\RFPBundle\Entity\Request;

/**
 * Provides information about RFP entities that are related to currencies.
 */
class CurrencyCheckerProvider implements RepositoryCurrencyCheckerProviderInterface
{
    const ENTITY_LABEL = 'oro.rfp.request.entity_label';

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
        Organization $organization = null
    ) {
        $rfpRepository = $this->doctrine->getRepository(Request::class);
        return $rfpRepository->hasRecordsWithRemovingCurrencies($removingCurrencies, $organization);
    }
}

<?php

namespace Oro\Bundle\RFPBundle\Provider;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\CurrencyBundle\Provider\RepositoryCurrencyCheckerProviderInterface;

class CurrencyCheckerProvider implements RepositoryCurrencyCheckerProviderInterface
{
    const ENTITY_LABEL = 'oro.rfp.request.entity_label';

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
        $rfpRepository = $this->doctrine->getRepository('OroRFPBundle:Request');
        return $rfpRepository->hasRecordsWithRemovingCurrencies($removingCurrencies, $organization);
    }
}

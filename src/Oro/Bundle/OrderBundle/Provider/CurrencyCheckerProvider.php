<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Oro\Bundle\CurrencyBundle\Provider\RepositoryCurrencyCheckerProviderInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CurrencyCheckerProvider implements RepositoryCurrencyCheckerProviderInterface
{
    const ENTITY_LABEL = 'oro.order.entity_label';

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
        $orderRepository = $this->doctrine->getRepository('OroOrderBundle:Order');
        return $orderRepository->hasRecordsWithRemovingCurrencies($removingCurrencies, $organization);
    }
}

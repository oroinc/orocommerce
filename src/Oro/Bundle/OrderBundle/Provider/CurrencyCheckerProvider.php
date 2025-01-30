<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Provider\RepositoryCurrencyCheckerProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Provides information about orders with removing currencies
 */
class CurrencyCheckerProvider implements RepositoryCurrencyCheckerProviderInterface
{
    const ENTITY_LABEL = 'oro.order.entity_label';

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
        $orderRepository = $this->doctrine->getRepository(Order::class);
        return $orderRepository->hasRecordsWithRemovingCurrencies($removingCurrencies, $organization);
    }
}

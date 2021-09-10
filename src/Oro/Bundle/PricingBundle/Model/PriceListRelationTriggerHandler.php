<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Provides a set of methods to handle price list collection changes.
 *
 * @see \Oro\Bundle\PricingBundle\Async\PriceListRelationMessageFilter
 */
class PriceListRelationTriggerHandler implements PriceListRelationTriggerHandlerInterface, FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /**
     * @var PriceListRelationTriggerHandlerInterface
     */
    private $cplHandler;

    /**
     * @var PriceListRelationTriggerHandlerInterface
     */
    private $flatHandler;

    public function __construct(
        PriceListRelationTriggerHandlerInterface $cplHandler,
        PriceListRelationTriggerHandlerInterface $flatHandler
    ) {
        $this->cplHandler = $cplHandler;
        $this->flatHandler = $flatHandler;
    }

    /**
     * {@inheritDoc}
     */
    public function handleConfigChange(): void
    {
        $this->getHandler()->handleConfigChange();
    }

    /**
     * {@inheritDoc}
     */
    public function handleFullRebuild(): void
    {
        $this->getHandler()->handleFullRebuild();
    }

    /**
     * {@inheritDoc}
     */
    public function handleWebsiteChange(Website $website): void
    {
        $this->getHandler()->handleWebsiteChange($website);
    }

    /**
     * {@inheritDoc}
     */
    public function handleCustomerGroupChange(CustomerGroup $customerGroup, Website $website): void
    {
        $this->getHandler()->handleCustomerGroupChange($customerGroup, $website);
    }

    /**
     * {@inheritDoc}
     */
    public function handleCustomerGroupRemove(CustomerGroup $customerGroup): void
    {
        $this->getHandler()->handleCustomerGroupRemove($customerGroup);
    }

    /**
     * {@inheritDoc}
     */
    public function handleCustomerChange(Customer $customer, Website $website): void
    {
        $this->getHandler()->handleCustomerChange($customer, $website);
    }

    /**
     * {@inheritDoc}
     */
    public function handlePriceListStatusChange(PriceList $priceList): void
    {
        $this->getHandler()->handlePriceListStatusChange($priceList);
    }

    private function getHandler(): PriceListRelationTriggerHandlerInterface
    {
        if ($this->isFeaturesEnabled()) {
            return $this->flatHandler;
        }

        return $this->cplHandler;
    }
}

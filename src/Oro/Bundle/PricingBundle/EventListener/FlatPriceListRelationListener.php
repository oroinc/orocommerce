<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveFlatPriceTopic;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Handler\PriceListRelationHandler;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Responsible for updating the product price index when changing customer, customer group relations.
 */
class FlatPriceListRelationListener implements OptionalListenerInterface, FeatureToggleableInterface
{
    use OptionalListenerTrait, FeatureCheckerHolderTrait;

    private const FIELD_NAME = 'priceList';

    private PriceListRelationHandler $priceListRelationHandler;
    private MessageProducerInterface $producer;
    private ConfigManager $configManager;

    private const SUPPORTED_LEVEL = [
        PriceListToCustomer::class => ['customer'],
        PriceListToCustomerGroup::class => ['customer', 'customer_group']
    ];

    public function __construct(
        PriceListRelationHandler $priceListRelationHandler,
        MessageProducerInterface $producer,
        ConfigManager $configManager
    ) {
        $this->priceListRelationHandler = $priceListRelationHandler;
        $this->producer = $producer;
        $this->configManager = $configManager;
    }

    /**
     * The relation life cycle consists only of creating a relation and updating the price list in relation,
     * so it makes no sense to check whether a certain field has changed.
     */
    public function preUpdate(BasePriceListRelation $priceListRelation): void
    {
        $this->resolvePriceListRelation($priceListRelation);
    }

    public function prePersist(BasePriceListRelation $priceListRelation): void
    {
        $this->resolvePriceListRelation($priceListRelation);
    }

    private function resolvePriceListRelation(BasePriceListRelation $priceListRelation): void
    {
        if (!$this->isSupported(self::SUPPORTED_LEVEL[ClassUtils::getClass($priceListRelation)])) {
            return;
        }

        $priceList = $priceListRelation->getPriceList();
        if (!$this->priceListRelationHandler->isPriceListAlreadyUsed($priceList)) {
            $this->producer->send(ResolveFlatPriceTopic::getName(), ['priceList' => $priceList->getId()]);
        }
    }

    private function isSupported(array $supportedAccuracy): bool
    {
        $configAccuracy = $this->configManager->get('oro_pricing.price_indexation_accuracy');
        if ($this->enabled && $this->isFeaturesEnabled() && in_array($configAccuracy, $supportedAccuracy, true)) {
            return true;
        }

        return false;
    }
}

<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Brick\Math\BigDecimal;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTreeHandler;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\AbstractSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Subtotal provider for the Checkout entity.
 */
class SubtotalProvider extends AbstractSubtotalProvider implements
    SubtotalProviderInterface,
    FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    public const TYPE = 'subtotal';
    public const LABEL = 'oro.checkout.subtotals.checkout_subtotal.label';

    private TranslatorInterface $translator;

    private RoundingServiceInterface $rounding;

    private ProductLineItemPriceProviderInterface $productLineItemPriceProvider;

    private CombinedPriceListTreeHandler $priceListTreeHandler;

    private ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory;

    public function __construct(
        TranslatorInterface $translator,
        RoundingServiceInterface $rounding,
        ProductLineItemPriceProviderInterface $productLineItemPriceProvider,
        CombinedPriceListTreeHandler $priceListTreeHandler,
        SubtotalProviderConstructorArguments $arguments,
        ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory
    ) {
        parent::__construct($arguments);

        $this->translator = $translator;
        $this->rounding = $rounding;
        $this->productLineItemPriceProvider = $productLineItemPriceProvider;
        $this->priceListTreeHandler = $priceListTreeHandler;
        $this->priceScopeCriteriaFactory = $priceScopeCriteriaFactory;
    }

    #[\Override]
    public function isSupported($entity)
    {
        return $entity instanceof Checkout;
    }

    /**
     * Returns line items subtotal for current user currency
     *
     * @param Checkout $entity
     *
     * @return Subtotal|null
     */
    #[\Override]
    public function getSubtotal($entity)
    {
        if (!$this->isSupported($entity)) {
            return null;
        }

        return $this->getCheckoutSubtotal($entity, $this->getBaseCurrency($entity));
    }

    /**
     * @param Checkout $entity
     * @param string $currency
     *
     * @return Subtotal|null
     */
    public function getSubtotalByCurrency($entity, $currency)
    {
        if (!$this->isSupported($entity)) {
            return null;
        }

        return $this->getCheckoutSubtotal($entity, $currency);
    }

    /**
     * @param Checkout $entity
     * @param string $currency
     *
     * @return Subtotal
     */
    protected function getCheckoutSubtotal(Checkout $entity, string $currency): Subtotal
    {
        $subtotalAmount = BigDecimal::of(0);
        $lineItemsWithoutFixedPrice = [];

        foreach ($entity->getLineItems() as $lineItem) {
            // Calculates subtotal for line items with fixed price at first.
            if ($lineItem->isPriceFixed()) {
                if ($lineItem->getPrice() instanceof Price) {
                    $subtotalAmount = $subtotalAmount->plus($this->getLineItemSubtotal($lineItem));
                }
            } else {
                // Collects line items without fixed price to include them in subtotal further.
                $lineItemsWithoutFixedPrice[] = $lineItem;
            }
        }

        $subtotal = $this->createSubtotal();
        if ($lineItemsWithoutFixedPrice) {
            $priceScopeCriteria = $this->priceScopeCriteriaFactory->createByContext($entity);
            $this->setPriceListRelation($priceScopeCriteria, $subtotal);

            $productLineItemsPrices = $this->productLineItemPriceProvider
                ->getProductLineItemsPrices($lineItemsWithoutFixedPrice, $priceScopeCriteria, $currency);

            foreach ($productLineItemsPrices as $productLineItemPrice) {
                $subtotalAmount = $subtotalAmount->plus($productLineItemPrice->getSubtotal());
            }
        }

        $subtotal->setAmount($subtotalAmount->toFloat());
        $subtotal->setVisible($subtotal->getAmount() !== 0.0);
        $subtotal->setCurrency($currency);

        return $subtotal;
    }

    private function getLineItemSubtotal(CheckoutLineItem $lineItem): float
    {
        $lineItemSubtotal = BigDecimal::of($lineItem->getPrice()->getValue())->multipliedBy($lineItem->getQuantity());

        return $this->rounding->round($lineItemSubtotal->toFloat());
    }

    /**
     * @return Subtotal
     */
    protected function createSubtotal()
    {
        $subtotal = new Subtotal();
        $subtotal->setLabel($this->translator->trans(self::LABEL));
        $subtotal->setVisible(false);
        $subtotal->setType(self::TYPE);
        $subtotal->setRemovable(false);

        return $subtotal;
    }

    protected function setPriceListRelation(ProductPriceScopeCriteriaInterface $criteria, Subtotal $subtotal)
    {
        if ($this->isFeaturesEnabled()) {
            $priceList = $this->priceListTreeHandler
                ->getPriceList($criteria->getCustomer(), $criteria->getWebsite());
            $subtotal->setPriceList($priceList);
        }
    }
}

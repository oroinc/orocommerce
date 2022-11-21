<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTreeHandler;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\AbstractSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Subtotal provider for the Checkout entity.
 */
class CheckoutSubtotalProvider extends AbstractSubtotalProvider implements
    SubtotalProviderInterface,
    FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    const TYPE = 'subtotal';
    const LABEL = 'oro.checkout.subtotals.checkout_subtotal.label';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var RoundingServiceInterface */
    protected $rounding;

    /** @var ProductPriceProviderInterface */
    protected $productPriceProvider;

    /** @var CombinedPriceListTreeHandler */
    protected $priceListTreeHandler;

    /** @var ProductPriceScopeCriteriaFactoryInterface */
    protected $priceScopeCriteriaFactory;

    public function __construct(
        TranslatorInterface $translator,
        RoundingServiceInterface $rounding,
        ProductPriceProviderInterface $productPriceProvider,
        CombinedPriceListTreeHandler $priceListTreeHandler,
        SubtotalProviderConstructorArguments $arguments,
        ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory
    ) {
        parent::__construct($arguments);

        $this->translator = $translator;
        $this->rounding = $rounding;
        $this->productPriceProvider = $productPriceProvider;
        $this->priceListTreeHandler = $priceListTreeHandler;
        $this->priceScopeCriteriaFactory = $priceScopeCriteriaFactory;
    }

    /**
     * {@inheritDoc}
     */
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
    protected function getCheckoutSubtotal(Checkout $entity, $currency)
    {
        $subtotalAmount = 0.0;
        $subtotal = $this->createSubtotal();

        $productsPriceCriteria = $this->prepareProductsPriceCriteria($entity, $currency);
        if ($productsPriceCriteria) {
            $searchScope = $this->priceScopeCriteriaFactory->createByContext($entity);
            $this->setPriceListRelation($searchScope, $subtotal);
            $prices = $this->productPriceProvider->getMatchedPrices($productsPriceCriteria, $searchScope);
            /** @var Price $price */
            foreach ($prices as $identifier => $price) {
                if ($price && array_key_exists($identifier, $productsPriceCriteria)) {
                    $priceValue = $price->getValue();
                    $subtotalAmount += (float)$priceValue * $productsPriceCriteria[$identifier]->getQuantity();
                    $subtotal->setVisible(true);
                }
            }
        }

        foreach ($entity->getLineItems() as $lineItem) {
            if ($lineItem->isPriceFixed() && $lineItem->getPrice() instanceof Price) {
                $subtotalAmount += $this->getRowTotal($lineItem, $currency);
            }
        }
        $subtotal->setAmount($this->rounding->round($subtotalAmount));
        $subtotal->setCurrency($currency);

        return $subtotal;
    }

    /**
     * @param Checkout $checkout
     * @param string $currency
     *
     * @return ProductPriceCriteria[]
     */
    protected function prepareProductsPriceCriteria(Checkout $checkout, $currency)
    {
        $productsPriceCriteria = [];
        foreach ($checkout->getLineItems() as $lineItem) {
            if ($lineItem->isPriceFixed()) {
                continue;
            }
            $product = $lineItem->getProduct();
            $productUnit = $lineItem->getProductUnit();
            $quantity = $lineItem->getQuantity();
            if ($product && $productUnit && $quantity) {
                $quantity = (float)$lineItem->getQuantity();
                $criteria = new ProductPriceCriteria($product, $productUnit, $quantity, $currency);
                $productsPriceCriteria[$criteria->getIdentifier()] = $criteria;
            }
        }

        return $productsPriceCriteria;
    }

    /**
     * @param CheckoutLineItem $lineItem
     * @param string $currency
     *
     * @return float
     */
    protected function getRowTotal(CheckoutLineItem $lineItem, $currency)
    {
        if (!$lineItem->isPriceFixed() || !$lineItem->getPrice()) {
            return 0.0;
        }

        $rowSubtotal = $lineItem->getPrice()->getValue() * $lineItem->getQuantity();
        $rowCurrency = $lineItem->getPrice()->getCurrency();

        if ($currency !== $rowCurrency) {
            $rowSubtotal *= $this->getExchangeRate($rowCurrency, $currency);
        }

        return $rowSubtotal;
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

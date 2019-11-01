<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Model\PriceListTreeHandler;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\AbstractSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Get Subtotal for the Checkout entity in the given currency
 */
class CheckoutSubtotalProvider extends AbstractSubtotalProvider implements SubtotalProviderInterface
{
    const TYPE = 'subtotal';
    const NAME = 'oro.checkout.subtotals.checkout_subtotal';
    const LABEL = 'oro.checkout.subtotals.checkout_subtotal.label';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var RoundingServiceInterface */
    protected $rounding;

    /** @var ProductPriceProviderInterface */
    protected $productPriceProvider;

    /** @var PriceListTreeHandler */
    protected $priceListTreeHandler;

    /** @var FeatureChecker */
    protected $featureChecker;

    /** @var ProductPriceScopeCriteriaFactoryInterface */
    protected $priceScopeCriteriaFactory;

    /**
     * @param TranslatorInterface $translator
     * @param RoundingServiceInterface $rounding
     * @param ProductPriceProviderInterface $productPriceProvider
     * @param PriceListTreeHandler $priceListTreeHandler ,
     * @param SubtotalProviderConstructorArguments $arguments
     * @param FeatureChecker $featureChecker
     * @param ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory
     */
    public function __construct(
        TranslatorInterface $translator,
        RoundingServiceInterface $rounding,
        ProductPriceProviderInterface $productPriceProvider,
        PriceListTreeHandler $priceListTreeHandler,
        SubtotalProviderConstructorArguments $arguments,
        FeatureChecker $featureChecker,
        ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory
    ) {
        parent::__construct($arguments);

        $this->translator = $translator;
        $this->rounding = $rounding;
        $this->productPriceProvider = $productPriceProvider;
        $this->priceListTreeHandler = $priceListTreeHandler;
        $this->featureChecker = $featureChecker;
        $this->priceScopeCriteriaFactory = $priceScopeCriteriaFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
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
                    $rowTotal = (float)$priceValue * $productsPriceCriteria[$identifier]->getQuantity();
                    $subtotalAmount += $this->rounding->round($rowTotal);
                    $subtotal->setVisible(true);
                }
            }
        }

        foreach ($entity->getLineItems() as $lineItem) {
            if ($lineItem->isPriceFixed() && $lineItem->getPrice() instanceof Price) {
                $rowTotal = $this->getRowTotal($lineItem, $currency);
                $subtotalAmount += $this->rounding->round($rowTotal);
            }
        }
        $subtotal->setAmount($subtotalAmount);
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

        return $subtotal;
    }

    /**
     * @param ProductPriceScopeCriteriaInterface $criteria
     * @param Subtotal $subtotal
     */
    protected function setPriceListRelation(ProductPriceScopeCriteriaInterface $criteria, Subtotal $subtotal)
    {
        if ($this->featureChecker->isFeatureEnabled('oro_price_lists')) {
            $priceList = $this->priceListTreeHandler
                ->getPriceList($criteria->getCustomer(), $criteria->getWebsite());
            $subtotal->setCombinedPriceList($priceList);
        }
    }
}

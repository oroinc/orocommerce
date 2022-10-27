<?php

namespace Oro\Bundle\PricingBundle\SubtotalProcessor\Provider;

use Brick\Math\BigDecimal;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use Oro\Bundle\ProductBundle\Model\QuantityAwareInterface;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Subtotal provider for line items without prices. SUM(ROUND(price*qty))
 */
class LineItemNotPricedSubtotalProvider extends AbstractSubtotalProvider implements SubtotalProviderInterface
{
    const TYPE = 'subtotal';
    const LABEL = 'oro.pricing.subtotals.not_priced_subtotal.label';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var RoundingServiceInterface */
    protected $rounding;

    /** @var ProductPriceProviderInterface */
    protected $productPriceProvider;

    /** @var string */
    protected $productClass;

    /** @var string */
    protected $productUnitClass;

    /** @var ProductPriceScopeCriteriaFactoryInterface */
    protected $priceScopeCriteriaFactory;

    public function __construct(
        TranslatorInterface $translator,
        RoundingServiceInterface $rounding,
        ProductPriceProviderInterface $productPriceProvider,
        SubtotalProviderConstructorArguments $arguments,
        ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory
    ) {
        parent::__construct($arguments);

        $this->translator = $translator;
        $this->rounding = $rounding;
        $this->productPriceProvider = $productPriceProvider;
        $this->priceScopeCriteriaFactory = $priceScopeCriteriaFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function isSupported($entity)
    {
        return $entity instanceof LineItemsNotPricedAwareInterface;
    }

    /**
     * Get line items subtotal for current user currency
     *
     * @param LineItemsNotPricedAwareInterface|CustomerOwnerAwareInterface|WebsiteAwareInterface $entity
     *
     * @return Subtotal
     */
    public function getSubtotal($entity)
    {
        return $this->getSubtotalByCurrency($entity, $this->getBaseCurrency($entity));
    }

    /**
     * @param LineItemsNotPricedAwareInterface|CustomerOwnerAwareInterface|WebsiteAwareInterface $entity
     * @param string $currency
     * @return Subtotal
     */
    public function getSubtotalByCurrency($entity, $currency)
    {
        if (!$entity instanceof LineItemsNotPricedAwareInterface) {
            return null;
        }

        $subtotalAmount = BigDecimal::of(0);
        $subtotal = $this->createSubtotal();

        $productsPriceCriteria = $this->prepareProductsPriceCriteria($entity, $currency);
        if ($productsPriceCriteria) {
            $searchScope = $this->priceScopeCriteriaFactory->createByContext($entity);
            $prices = $this->productPriceProvider->getMatchedPrices($productsPriceCriteria, $searchScope);
            foreach ($prices as $identifier => $price) {
                if ($price instanceof Price) {
                    /** @var BigDecimal $priceValue */
                    $priceValue = BigDecimal::of($price->getValue());
                    $rowTotal = $priceValue->multipliedBy($productsPriceCriteria[$identifier]->getQuantity());
                    $subtotalAmount = $subtotalAmount->plus($this->rounding->round($rowTotal->toFloat()));
                    $subtotal->setVisible(true);
                }
            }
        }
        $subtotal->setAmount($subtotalAmount->toFloat());
        $subtotal->setCurrency($currency);

        return $subtotal;
    }

    /**
     * @param LineItemsNotPricedAwareInterface|CustomerOwnerAwareInterface|WebsiteAwareInterface $entity
     * @param string $currency
     * @return ProductPriceCriteria[]
     */
    protected function prepareProductsPriceCriteria($entity, $currency)
    {
        $productsPriceCriteria = [];
        foreach ($entity->getLineItems() as $lineItem) {
            if ($lineItem instanceof ProductHolderInterface
                && $lineItem instanceof ProductUnitHolderInterface
                && $lineItem instanceof QuantityAwareInterface
            ) {
                $hasProduct = $lineItem->getProduct() && $lineItem->getProduct()->getId();
                $hasProductUnitCode = $lineItem->getProductUnit() && $lineItem->getProductUnit()->getCode();
                if ($hasProduct && $hasProductUnitCode) {
                    $quantity = (float)$lineItem->getQuantity();
                    $criteria = new ProductPriceCriteria(
                        $lineItem->getProduct(),
                        $lineItem->getProductUnit(),
                        $quantity,
                        $currency
                    );
                    $productsPriceCriteria[$criteria->getIdentifier()] = $criteria;
                }
            }
        }

        return $productsPriceCriteria;
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
}

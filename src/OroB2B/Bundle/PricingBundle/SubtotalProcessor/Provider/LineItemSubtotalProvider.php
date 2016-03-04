<?php

namespace OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\CurrencyBundle\Entity\CurrencyAwareInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;

use OroB2B\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use OroB2B\Bundle\PricingBundle\Entity\QuantityAwareInterface;
use OroB2B\Bundle\PricingBundle\Model\ProductPriceCriteria;
use OroB2B\Bundle\PricingBundle\Provider\ProductPriceProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Model\ProductHolderInterface;
use OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;

class LineItemSubtotalProvider implements SubtotalProviderInterface
{
    const TYPE = 'subtotal';
    const NAME = 'orob2b_pricing.subtotals';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var RoundingServiceInterface */
    protected $rounding;

    /** @var ProductPriceProvider */
    protected $productPriceProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var string */
    protected $productClass;

    /** @var string */
    protected $productUnitClass;

    /**
     * @param TranslatorInterface $translator
     * @param RoundingServiceInterface $rounding
     * @param ProductPriceProvider $productPriceProvider
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        TranslatorInterface $translator,
        RoundingServiceInterface $rounding,
        ProductPriceProvider $productPriceProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->translator = $translator;
        $this->rounding = $rounding;
        $this->productPriceProvider = $productPriceProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function isSupported($entity)
    {
        return $entity instanceof LineItemsAwareInterface;
    }

    /**
     * Get line items subtotal
     *
     * @param LineItemsAwareInterface $entity
     *
     * @return Subtotal
     */
    public function getSubtotal($entity)
    {
        $subtotalAmount = 0.0;
        $subtotal = new Subtotal();
        $subtotal->setLabel($this->translator->trans('orob2b.pricing.subtotals.subtotal.label'));

        $baseCurrency = $this->getBaseCurrency($entity);
        foreach ($entity->getLineItems() as $lineItem) {
            if ($lineItem instanceof PriceAwareInterface && $lineItem->getPrice() instanceof Price) {
                $subtotalAmount += $this->getRowTotal($lineItem, $baseCurrency);
            } elseif ($lineItem instanceof ProductHolderInterface
                && $lineItem instanceof ProductUnitHolderInterface
                && $lineItem instanceof QuantityAwareInterface
            ) {
                $productsPriceCriteria =
                    $this->prepareProductsPriceCriteria($lineItem, $this->getBaseCurrency($entity));
                $price = $this->productPriceProvider->getMatchedPrices($productsPriceCriteria);
                $subtotalAmount += reset($price)->getValue() * $lineItem->getQuantity();
            }
        }

        $subtotal->setAmount($this->rounding->round($subtotalAmount));
        $subtotal->setCurrency($baseCurrency);

        return $subtotal;
    }

    /**
     * @param PriceAwareInterface $lineItem
     * @param string $baseCurrency
     * @return float|int
     */
    protected function getRowTotal(PriceAwareInterface $lineItem, $baseCurrency)
    {
        $rowTotal = $lineItem->getPrice()->getValue();
        $rowCurrency = $lineItem->getPrice()->getCurrency();

        if ($lineItem instanceof PriceTypeAwareInterface &&
            $lineItem instanceof QuantityAwareInterface &&
            (int)$lineItem->getPriceType() === PriceTypeAwareInterface::PRICE_TYPE_UNIT
        ) {
            $rowTotal *= $lineItem->getQuantity();
        }

        if ($baseCurrency !== $rowCurrency) {
            $rowTotal *= $this->getExchangeRate($rowCurrency, $baseCurrency);
        }

        return $rowTotal;
    }

    /**
     * @param $entity
     * @return string
     */
    protected function getBaseCurrency($entity)
    {
        if (!$entity instanceof CurrencyAwareInterface) {
            return 'USD';
        } else {
            return $entity->getCurrency();
        }
    }

    /**
     * @param string $fromCurrency
     * @param string $toCurrency
     * @return float
     */
    protected function getExchangeRate($fromCurrency, $toCurrency)
    {
        /**
         * TODO: Need to define currency exchange logic. BB-124
         */
        return 1.0;
    }

    /**
     * @param ProductHolderInterface|ProductUnitHolderInterface|QuantityAwareInterface $lineItem
     * @param string $currency
     * @return ProductPriceCriteria[]
     */
    protected function prepareProductsPriceCriteria($lineItem, $currency)
    {
        $productsPriceCriteria = [];

        $productId = $lineItem->getProduct()->getId();
        $productUnitCode = $lineItem->getProductUnit()->getCode();

        if ($productId && $productUnitCode) {
            /** @var Product $product */
            $product = $this->getEntityReference($this->productClass, $productId);
            /** @var ProductUnit $unit */
            $unit = $this->getEntityReference($this->productUnitClass, $productUnitCode);
            $quantity = (float)$lineItem->getQuantity();
            $productsPriceCriteria[] = new ProductPriceCriteria($product, $unit, $quantity, $currency);
        }

        return $productsPriceCriteria;
    }


    /**
     * @param string $productClass
     */
    public function setProductClass($productClass)
    {
        $this->productClass = $productClass;
    }

    /**
     * @param string $productUnitClass
     */
    public function setProductUnitClass($productUnitClass)
    {
        $this->productUnitClass = $productUnitClass;
    }

    /**
     * @param string $class
     * @param mixed $id
     * @return object
     */
    protected function getEntityReference($class, $id)
    {
        return $this->getManagerForClass($class)->getReference($class, $id);
    }


    /**
     * @param string $class
     * @return EntityManager
     */
    protected function getManagerForClass($class)
    {
        return $this->doctrineHelper->getEntityManagerForClass($class);
    }
}

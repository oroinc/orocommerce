<?php

namespace Oro\Bundle\PricingBundle\SubtotalProcessor\Provider;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\PricingBundle\Model\PriceListTreeHandler;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use Oro\Bundle\ProductBundle\Model\QuantityAwareInterface;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Subtotal provider for line items without prices. SUM(ROUND(price*qty))
 */
class LineItemNotPricedSubtotalProvider extends AbstractSubtotalProvider implements SubtotalProviderInterface
{
    const TYPE = 'subtotal';
    const NAME = 'oro.pricing.subtotals.not_priced_subtotal';
    const LABEL = 'oro.pricing.subtotals.not_priced_subtotal.label';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var RoundingServiceInterface */
    protected $rounding;

    /** @var ProductPriceProvider */
    protected $productPriceProvider;

    /** @var string */
    protected $productClass;

    /** @var string */
    protected $productUnitClass;

    /** @var PriceListTreeHandler */
    protected $priceListTreeHandler;

    /**
     * @param TranslatorInterface $translator
     * @param RoundingServiceInterface $rounding
     * @param ProductPriceProvider $productPriceProvider
     * @param PriceListTreeHandler $priceListTreeHandler ,
     * @param SubtotalProviderConstructorArguments $arguments
     */
    public function __construct(
        TranslatorInterface $translator,
        RoundingServiceInterface $rounding,
        ProductPriceProvider $productPriceProvider,
        PriceListTreeHandler $priceListTreeHandler,
        SubtotalProviderConstructorArguments $arguments
    ) {
        parent::__construct($arguments);

        $this->translator = $translator;
        $this->rounding = $rounding;
        $this->productPriceProvider = $productPriceProvider;
        $this->priceListTreeHandler = $priceListTreeHandler;
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
        $subtotalAmount = 0.0;
        $subtotal = $this->createSubtotal();

        $productsPriceCriterias = $this->prepareProductsPriceCriterias($entity, $currency);
        if ($productsPriceCriterias) {
            $priceList = $this->priceListTreeHandler->getPriceList($entity->getCustomer(), $entity->getWebsite());
            $prices = $this->productPriceProvider->getMatchedPrices($productsPriceCriterias, $priceList);
            foreach ($prices as $identifier => $price) {
                if ($price) {
                    $priceValue = $price->getValue();
                    $rowTotal = (float)$priceValue * $productsPriceCriterias[$identifier]->getQuantity();
                    $subtotalAmount += $this->rounding->round($rowTotal);
                    $subtotal->setVisible(true);
                }
            }
        }
        $subtotal->setAmount($subtotalAmount);
        $subtotal->setCurrency($currency);

        return $subtotal;
    }

    /**
     * @param LineItemsNotPricedAwareInterface|CustomerOwnerAwareInterface|WebsiteAwareInterface $entity
     * @param string $currency
     * @return ProductPriceCriteria[]
     */
    protected function prepareProductsPriceCriterias($entity, $currency)
    {
        $productsPriceCriterias = [];
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
                    $productsPriceCriterias[$criteria->getIdentifier()] = $criteria;
                }
            }
        }

        return $productsPriceCriterias;
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

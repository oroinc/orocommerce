<?php

namespace OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\AccountBundle\Entity\AccountOwnerAwareInterface;
use OroB2B\Bundle\ProductBundle\Model\QuantityAwareInterface;
use OroB2B\Bundle\PricingBundle\Model\PriceListTreeHandler;
use OroB2B\Bundle\PricingBundle\Model\ProductPriceCriteria;
use OroB2B\Bundle\PricingBundle\Provider\ProductPriceProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Model\ProductHolderInterface;
use OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;
use OroB2B\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;
use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;

class LineItemNotPricedSubtotalProvider extends AbstractSubtotalProvider implements SubtotalProviderInterface
{
    const TYPE = 'subtotal';
    const NAME = 'orob2b.pricing.subtotals.not_priced_subtotal';
    const LABEL = 'orob2b.pricing.subtotals.not_priced_subtotal.label';

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

    /** @var PriceListTreeHandler */
    protected $priceListTreeHandler;

    /**
     * @param TranslatorInterface $translator
     * @param RoundingServiceInterface $rounding
     * @param ProductPriceProvider $productPriceProvider
     * @param DoctrineHelper $doctrineHelper
     * @param PriceListTreeHandler $priceListTreeHandler ,
     * @param UserCurrencyManager $currencyManager
     */
    public function __construct(
        TranslatorInterface $translator,
        RoundingServiceInterface $rounding,
        ProductPriceProvider $productPriceProvider,
        DoctrineHelper $doctrineHelper,
        PriceListTreeHandler $priceListTreeHandler,
        UserCurrencyManager $currencyManager
    ) {
        parent::__construct($currencyManager);

        $this->translator = $translator;
        $this->rounding = $rounding;
        $this->productPriceProvider = $productPriceProvider;
        $this->doctrineHelper = $doctrineHelper;
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
     * @param LineItemsNotPricedAwareInterface|AccountOwnerAwareInterface|WebsiteAwareInterface $entity
     *
     * @return Subtotal
     */
    public function getSubtotal($entity)
    {
        return $this->getSubtotalByCurrency($entity, $this->getBaseCurrency($entity));
    }

    /**
     * @param LineItemsNotPricedAwareInterface|AccountOwnerAwareInterface|WebsiteAwareInterface $entity
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
            $priceList = $this->priceListTreeHandler->getPriceList($entity->getAccount(), $entity->getWebsite());
            $prices = $this->productPriceProvider->getMatchedPrices($productsPriceCriterias, $priceList);
            foreach ($prices as $identifier => $price) {
                if ($price) {
                    $priceValue = $price->getValue();
                    $subtotalAmount += (float) $priceValue * $productsPriceCriterias[$identifier]->getQuantity();
                    $subtotal->setVisible(true);
                }
            }
        }
        $subtotal->setAmount($this->rounding->round($subtotalAmount));
        $subtotal->setCurrency($currency);

        return $subtotal;
    }

    /**
     * @param LineItemsNotPricedAwareInterface|AccountOwnerAwareInterface|WebsiteAwareInterface $entity
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
                $productId = $lineItem->getProduct()->getId();
                $productUnitCode = $lineItem->getProductUnit()->getCode();
                if ($productId && $productUnitCode) {
                    /** @var Product $product */
                    $product = $this->getEntityReference($this->productClass, $productId);
                    /** @var ProductUnit $unit */
                    $unit = $this->getEntityReference($this->productUnitClass, $productUnitCode);
                    $quantity = (float)$lineItem->getQuantity();
                    $criteria = new ProductPriceCriteria($product, $unit, $quantity, $currency);
                    $productsPriceCriterias[$criteria->getIdentifier()] = $criteria;
                }
            }
        }

        return $productsPriceCriterias;
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

<?php

namespace OroB2B\Bundle\RFPBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
use OroB2B\Bundle\PricingBundle\Model\PriceListTreeHandler;
use OroB2B\Bundle\PricingBundle\Model\ProductPriceCriteria;
use OroB2B\Bundle\PricingBundle\Provider\ProductPriceProvider;
use OroB2B\Bundle\ProductBundle\Storage\DataStorageInterface;

class OrderDataStorageExtension extends AbstractTypeExtension
{
    /**
     * @var string
     */
    protected $extendedType;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var ProductPriceProvider
     */
    protected $productPriceProvider;

    /**
     * @var PriceListTreeHandler
     */
    protected $priceListTreeHandler;

    /**
     * @var array
     */
    protected $productPriceCriteriaCache = [];

    /**
     * @param RequestStack $requestStack
     * @param ProductPriceProvider $productPriceProvider
     * @param PriceListTreeHandler $priceListTreeHandler
     */
    public function __construct(
        RequestStack $requestStack,
        ProductPriceProvider $productPriceProvider,
        PriceListTreeHandler $priceListTreeHandler
    ) {
        $this->requestStack = $requestStack;
        $this->productPriceProvider = $productPriceProvider;
        $this->priceListTreeHandler = $priceListTreeHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return $this->extendedType;
    }

    /**
     * @param string $extendedType
     */
    public function setExtendedType($extendedType)
    {
        $this->extendedType = $extendedType;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->isApplicable()) {
            return;
        }
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $entity = $event->getData();
            if ($entity instanceof Order && !$entity->getId()) {
                $this->fillData($entity);
            }
        });
    }

    /**
     * @param Order $order
     */
    protected function fillData($order)
    {
        /** @var array[] $lineItems */
        $lineItems = [];
        $productsPriceCriteria = [];
        foreach ($order->getLineItems()->toArray() as $lineItem) {
            /** @var OrderLineItem $lineItem */
            try {
                $criteria = new ProductPriceCriteria(
                    $lineItem->getProduct(),
                    $lineItem->getProductUnit(),
                    $lineItem->getQuantity(),
                    $order->getCurrency()
                );
            } catch (\InvalidArgumentException $e) {
                continue;
            }
            $lineItems[$criteria->getIdentifier()][] = $lineItem;
            $productsPriceCriteria[] = $criteria;
        }
        if (count($productsPriceCriteria) === 0) {
            return;
        }
        $matchedPrices = $this->productPriceProvider->getMatchedPrices(
            $productsPriceCriteria,
            $this->getPriceList($order)
        );
        foreach ($matchedPrices as $identifier => $price) {
            foreach ($lineItems[$identifier] as $lineItem) {
                $lineItem->setPrice($price);
            }
        }
    }

    /**
     * @return bool
     */
    protected function isApplicable()
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request && $request->get(DataStorageInterface::STORAGE_KEY);
    }

    /**
     * @param Order $order
     * @return BasePriceList
     */
    protected function getPriceList(Order $order)
    {
        return $this->priceListTreeHandler->getPriceList($order->getAccount(), $order->getWebsite());
    }
}

<?php

namespace Oro\Bundle\RFPBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Model\PriceListTreeHandler;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProvider;
use Oro\Bundle\ProductBundle\Storage\DataStorageInterface;

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

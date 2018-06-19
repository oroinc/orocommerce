<?php

namespace Oro\Bundle\RFPBundle\Form\Extension;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Storage\DataStorageInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;

class OrderDataStorageExtension extends AbstractTypeExtension implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /**
     * @var string
     */
    protected $extendedType;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var ProductPriceProviderInterface
     */
    protected $productPriceProvider;

    /**
     * @var array
     */
    protected $productPriceCriteriaCache = [];

    /**
     * @param RequestStack $requestStack
     * @param ProductPriceProviderInterface $productPriceProvider
     */
    public function __construct(
        RequestStack $requestStack,
        ProductPriceProviderInterface $productPriceProvider
    ) {
        $this->requestStack = $requestStack;
        $this->productPriceProvider = $productPriceProvider;
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
            $this->getPriceScopeCriteria($order)
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
        return $this->isFeaturesEnabled() && $request && $request->get(DataStorageInterface::STORAGE_KEY);
    }

    /**
     * @param Order $order
     * @return ProductPriceScopeCriteriaInterface
     */
    protected function getPriceScopeCriteria(Order $order): ProductPriceScopeCriteriaInterface
    {
        $scopeCriteria = new ProductPriceScopeCriteria();
        $scopeCriteria->setCustomer($order->getCustomer());
        $scopeCriteria->setWebsite($order->getWebsite());

        return $scopeCriteria;
    }
}

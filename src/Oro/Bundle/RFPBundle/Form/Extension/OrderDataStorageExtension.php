<?php

namespace Oro\Bundle\RFPBundle\Form\Extension;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Storage\DataStorageInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Creates order line items based on RFP.
 */
class OrderDataStorageExtension extends AbstractTypeExtension implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    private RequestStack $requestStack;
    private ProductPriceProviderInterface $productPriceProvider;
    private ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory;
    private ProductPriceCriteriaFactoryInterface $productPriceCriteriaFactory;

    public function __construct(
        RequestStack $requestStack,
        ProductPriceProviderInterface $productPriceProvider,
        ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory,
        ProductPriceCriteriaFactoryInterface $productPriceCriteriaFactory
    ) {
        $this->requestStack = $requestStack;
        $this->productPriceProvider = $productPriceProvider;
        $this->priceScopeCriteriaFactory = $priceScopeCriteriaFactory;
        $this->productPriceCriteriaFactory = $productPriceCriteriaFactory;
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [OrderType::class];
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
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

    private function fillData(Order $order): void
    {
        $productsPriceCriteria = $this->productPriceCriteriaFactory->createListFromProductLineItems(
            $order->getLineItems(),
            $order->getCurrency()
        );
        if (\count($productsPriceCriteria) === 0) {
            return;
        }

        $lineItems = [];
        foreach ($productsPriceCriteria as $key => $productPriceCriteria) {
            $lineItems[$productPriceCriteria->getIdentifier()][] = $order->getLineItems()->get($key);
        }

        $matchedPrices = $this->productPriceProvider->getMatchedPrices(
            $productsPriceCriteria,
            $this->priceScopeCriteriaFactory->createByContext($order)
        );

        foreach ($matchedPrices as $identifier => $price) {
            foreach ($lineItems[$identifier] as $lineItem) {
                $lineItem->setPrice($price);
            }
        }
    }

    private function isApplicable(): bool
    {
        if (!$this->isFeaturesEnabled()) {
            return false;
        }

        $request = $this->requestStack->getCurrentRequest();

        return null !== $request && $request->get(DataStorageInterface::STORAGE_KEY);
    }
}

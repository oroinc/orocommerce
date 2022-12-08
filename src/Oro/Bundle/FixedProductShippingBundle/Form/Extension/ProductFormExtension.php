<?php

namespace Oro\Bundle\FixedProductShippingBundle\Form\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FixedProductShippingBundle\Migrations\Data\ORM\LoadPriceAttributePriceListData;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Form\Type\ProductAttributePriceCollectionType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Adds shipping_cost attribute for product form.
 */
class ProductFormExtension extends AbstractTypeExtension
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [ProductType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$this->getPriceListShippingCostAttribute()) {
            return;
        }

        $builder->add(LoadPriceAttributePriceListData::SHIPPING_COST_FIELD, CollectionType::class, [
            'mapped' => false,
            'entry_type' => ProductAttributePriceCollectionType::class,
            'label' => false,
            'required' => false,
            'attr' => ['class' => 'fixed-product-shipping-cost']
        ]);

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    public function onPostSetData(FormEvent $event): void
    {
        /** @var Product $product */
        $product = $event->getData();
        $attribute = $this->getPriceListShippingCostAttribute();
        $existingPrices = $this->getProductPricesByShippingAttribute($product, $attribute);

        $formData = $this->transformPriceAttributes($product, $attribute, $existingPrices);
        $event->getForm()->get(LoadPriceAttributePriceListData::SHIPPING_COST_FIELD)->setData($formData);
    }

    public function onPostSubmit(FormEvent $event): void
    {
        $data = $event->getForm()->get(LoadPriceAttributePriceListData::SHIPPING_COST_FIELD)->getData();
        $entityManager = $this->registry->getManagerForClass(PriceAttributeProductPrice::class);

        foreach ($data as $attributePrices) {
            /** @var PriceAttributeProductPrice $price */
            foreach ($attributePrices as $price) {
                //remove nullable prices
                if ($price->getPrice()->getValue() === null) {
                    if (null !== $price->getId()) {
                        $entityManager->remove($price);
                    }
                    continue;
                }

                // persist new prices
                if (null === $price->getId()) {
                    $entityManager->persist($price);
                }
            }
        }
    }

    private function createPrice(array $newInstanceData, Product $product): PriceAttributeProductPrice
    {
        return (new PriceAttributeProductPrice())
            ->setUnit($newInstanceData['unit'])
            ->setProduct($product)
            ->setPrice(Price::create(null, $newInstanceData['currency']))
            ->setPriceList($newInstanceData['attribute']);
    }

    private function transformPriceAttributes(
        Product $product,
        PriceAttributePriceList $attribute,
        array $existingPrices
    ): array {
        $neededPrices = [];
        $unites = $product->getAvailableUnits();

        if ($attribute) {
            foreach ($attribute->getCurrencies() as $currency) {
                foreach ($unites as $unit) {
                    $neededPrices[] = ['attribute' => $attribute, 'currency' => $currency, 'unit' => $unit];
                }
            }
        }

        $formData = [];
        foreach ($existingPrices as $price) {
            $formData[$price->getPriceList()->getId()][] = $price;
            $neededPricesKey = array_search(
                [
                    'attribute' => $price->getPriceList(),
                    'currency' => $price->getPrice()->getCurrency(),
                    'unit' => $price->getUnit()
                ],
                $neededPrices,
                true
            );

            if (false !== $neededPricesKey) {
                unset($neededPrices[$neededPricesKey]);
            }
        }

        foreach ($neededPrices as $newPriceInstanceData) {
            $price = $this->createPrice($newPriceInstanceData, $product);
            $formData[$price->getPriceList()->getId()][] = $price;
        }

        return $formData;
    }

    private function getPriceListShippingCostAttribute(): ?PriceAttributePriceList
    {
        return $this->registry->getRepository(PriceAttributePriceList::class)
            ->findOneBy(['name' => LoadPriceAttributePriceListData::SHIPPING_COST_NAME]);
    }

    private function getProductPricesByShippingAttribute(Product $product, PriceAttributePriceList $priceList): array
    {
        $result = $this->registry
            ->getRepository(PriceAttributeProductPrice::class)
            ->findBy(['product' => $product, 'priceList' => $priceList]);

        return $result ?? [];
    }
}

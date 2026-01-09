<?php

namespace Oro\Bundle\FixedProductShippingBundle\Form\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FixedProductShippingBundle\Form\Type\FixedProductShippingOptionsCollectionType;
use Oro\Bundle\FixedProductShippingBundle\Migrations\Data\ORM\LoadPriceAttributePriceListData;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\SecurityBundle\Form\FieldAclHelper;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Adds shipping_cost attribute for product form.
 */
class ProductFormExtension extends AbstractTypeExtension
{
    public function __construct(private ManagerRegistry $registry, private FieldAclHelper $fieldAclHelper)
    {
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [ProductType::class];
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$this->getPriceListShippingCostAttribute()) {
            return;
        }

        $builder->add(
            LoadPriceAttributePriceListData::SHIPPING_COST_FIELD,
            FixedProductShippingOptionsCollectionType::class,
            ['mapped' => false, 'label' => false, 'required' => false]
        );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    public function onPreSetData(FormEvent $event): void
    {
        if (!$this->fieldAclHelper->isFieldAclEnabled(Product::class)) {
            return;
        }

        # Block any changes for the 'shipping cost' field if access to 'unit precisions' is blocked.
        $isUnitGranted = $this->fieldAclHelper->isFieldModificationGranted($event->getData(), 'unitPrecisions');
        FormUtils::replaceFieldOptionsRecursive(
            $event->getForm(),
            LoadPriceAttributePriceListData::SHIPPING_COST_FIELD,
            [
                'allow_add' => $isUnitGranted,
                'allow_delete' => $isUnitGranted,
                'check_field_name' => 'unitPrecisions',
                'prototype' => null
            ]
        );
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
        $form = $event->getForm();
        if (!$form->has(LoadPriceAttributePriceListData::SHIPPING_COST_FIELD)) {
            return;
        }
        $data = $form->get(LoadPriceAttributePriceListData::SHIPPING_COST_FIELD)->getData();
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

                // In case prices were created before the product was persisted,
                // productSku in the price may be null and needs to be updated
                $this->ensureProductSkuInPrice($price);

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

    private function ensureProductSkuInPrice(PriceAttributeProductPrice $price): void
    {
        if (
            null === $price->getProductSku()
            && $price->getProduct()?->getSku()
        ) {
            $price->setProduct($price->getProduct());
        }
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

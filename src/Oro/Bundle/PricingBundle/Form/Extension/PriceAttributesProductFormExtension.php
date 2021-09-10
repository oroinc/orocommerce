<?php

namespace Oro\Bundle\PricingBundle\Form\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributePriceListRepository;
use Oro\Bundle\PricingBundle\Form\Type\ProductAttributePriceCollectionType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Responsible for render a form with price attributes for product.
 */
class PriceAttributesProductFormExtension extends AbstractTypeExtension
{
    const PRODUCT_PRICE_ATTRIBUTES_PRICES = 'productPriceAttributesPrices';

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var AclHelper
     */
    private $aclHelper;

    public function __construct(ManagerRegistry $registry, AclHelper $aclHelper)
    {
        $this->registry = $registry;
        $this->aclHelper = $aclHelper;
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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(self::PRODUCT_PRICE_ATTRIBUTES_PRICES, CollectionType::class, [
            'mapped' => false,
            'entry_type' => ProductAttributePriceCollectionType::class,
            'label' => false,
            'required' => false,
        ]);

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPreSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    public function onPreSetData(FormEvent $event)
    {
        /** @var Product $product */
        $product = $event->getData();

        $neededPrices = $this->getAvailablePricesForProduct($product);
        $existingPrices = $this->getProductExistingPrices($product);

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

        $event->getForm()->get(self::PRODUCT_PRICE_ATTRIBUTES_PRICES)->setData($formData);
    }

    public function onPostSubmit(FormEvent $event)
    {
        $data = $event->getForm()->get(self::PRODUCT_PRICE_ATTRIBUTES_PRICES)->getData();

        foreach ($data as $attributePrices) {
            /** @var PriceAttributeProductPrice $price */
            foreach ($attributePrices as $price) {
                //remove nullable prices
                if ($price->getPrice()->getValue() === null) {
                    if (null !== $price->getId()) {
                        $this->registry->getManagerForClass(PriceAttributeProductPrice::class)->remove($price);
                    }
                    continue;
                }

                // persist new prices
                if (null === $price->getId()) {
                    $this->registry->getManagerForClass(PriceAttributeProductPrice::class)->persist($price);
                }
            }
        }
    }

    /**
     * @param array $newInstanceData
     * @param Product $product
     * @return PriceAttributeProductPrice
     */
    protected function createPrice(array $newInstanceData, Product $product)
    {
        return (new PriceAttributeProductPrice())
            ->setUnit($newInstanceData['unit'])
            ->setProduct($product)
            ->setPrice(Price::create(null, $newInstanceData['currency']))
            ->setPriceList($newInstanceData['attribute']);
    }

    /**
     * @param Product $product
     * @return array
     */
    private function getAvailablePricesForProduct(Product $product)
    {
        $neededPrices = [];
        $unites = $product->getAvailableUnits();

        /** @var PriceAttributePriceListRepository $priceAttributeRepository */
        $priceAttributeRepository = $this->registry
            ->getManagerForClass(PriceAttributePriceList::class)
            ->getRepository(PriceAttributePriceList::class);

        $qb = $priceAttributeRepository->getPriceAttributesQueryBuilder();
        /** @var PriceAttributePriceList[] $priceAttributes */
        $priceAttributes = $this->aclHelper->apply($qb)->getResult();

        foreach ($priceAttributes as $attribute) {
            foreach ($attribute->getCurrencies() as $currency) {
                foreach ($unites as $unit) {
                    $neededPrices[] = ['attribute' => $attribute, 'currency' => $currency, 'unit' => $unit];
                }
            }
        }

        return $neededPrices;
    }

    /**
     * @param $product
     * @return array|PriceAttributeProductPrice[]
     */
    private function getProductExistingPrices($product)
    {
        return $this->registry
            ->getManagerForClass(PriceAttributeProductPrice::class)
            ->getRepository(PriceAttributeProductPrice::class)
            ->findBy(['product' => $product]);
    }
}

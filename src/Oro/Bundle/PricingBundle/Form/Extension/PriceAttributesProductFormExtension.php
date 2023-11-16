<?php

namespace Oro\Bundle\PricingBundle\Form\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributePriceListRepository;
use Oro\Bundle\PricingBundle\Form\Type\ProductAttributePriceCollectionType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Responsible for render a form with price attributes for product.
 */
class PriceAttributesProductFormExtension extends AbstractTypeExtension
{
    const PRODUCT_PRICE_ATTRIBUTES_PRICES = 'productPriceAttributesPrices';

    private ManagerRegistry $registry;
    private AclHelper $aclHelper;
    private RequestStack $requestStack;

    public function __construct(ManagerRegistry $registry, AclHelper $aclHelper, RequestStack $requestStack)
    {
        $this->registry = $registry;
        $this->aclHelper = $aclHelper;
        $this->requestStack = $requestStack;
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

        $attributes = $this->getAvailablePriceListForProduct();
        $existingPrices = $this->getProductExistingPrices($product, $attributes);

        // Product unit needs to be set from the form to the product to show price fields on the product create form
        $this->ensureProductUnitPrecision($product);

        $formData = $this->transformPriceAttributes($product, $attributes, $existingPrices);
        $event->getForm()->get(self::PRODUCT_PRICE_ATTRIBUTES_PRICES)->setData($formData);
    }

    public function onPostSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        if (!$form->has(self::PRODUCT_PRICE_ATTRIBUTES_PRICES)) {
            return;
        }
        $data = $form->get(self::PRODUCT_PRICE_ATTRIBUTES_PRICES)->getData();

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

                // In case prices were created before the product was persisted,
                // productSku in the price may be null and needs to be updated
                $this->ensureProductSkuInPrice($price);

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

    private function ensureProductUnitPrecision(Product $product): void
    {
        if (null !== $product->getId()) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $requestProductData = $request->get('oro_product');

        if ($requestProductData
            && isset($requestProductData['primaryUnitPrecision']['unit'])
        ) {
            $unitCode = $requestProductData['primaryUnitPrecision']['unit'];

            $formProductUnit = $this->registry->getRepository(ProductUnit::class)
                ->find($unitCode);

            if ($formProductUnit) {
                $formProductUnitPrecision = new ProductUnitPrecision();
                $formProductUnitPrecision->setUnit($formProductUnit)
                    ->setProduct($product);

                if (isset($requestProductData['primaryUnitPrecision']['precision'])) {
                    $formProductUnitPrecision->setPrecision(
                        $requestProductData['primaryUnitPrecision']['precision']
                    );
                }

                $product->setPrimaryUnitPrecision($formProductUnitPrecision);
            }
        }
    }

    private function ensureProductSkuInPrice(PriceAttributeProductPrice $price): void
    {
        if (null === $price->getProductSku()
            && $price->getProduct()?->getSku()
        ) {
            $price->setProduct($price->getProduct());
        }
    }

    private function transformPriceAttributes(Product $product, array $attributes, array $existingPrices): array
    {
        $neededPrices = [];
        $unites = $product->getAvailableUnits();
        foreach ($attributes as $attribute) {
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

    /**
     * @return array
     */
    private function getAvailablePriceListForProduct()
    {
        /** @var PriceAttributePriceListRepository $priceAttributeRepository */
        $priceAttributeRepository = $this->registry
            ->getManagerForClass(PriceAttributePriceList::class)
            ->getRepository(PriceAttributePriceList::class);

        $qb = $priceAttributeRepository->getPriceAttributesQueryBuilder();
        $options = [self::PRODUCT_PRICE_ATTRIBUTES_PRICES => true];

        return $this->aclHelper->apply($qb, BasicPermission::VIEW, $options)->getResult() ?? [];
    }

    /**
     * @param Product $product
     * @param array $priceLists
     *
     * @return array|PriceAttributeProductPrice[]
     */
    private function getProductExistingPrices(Product $product, array $priceLists)
    {
        $result = $this->registry
            ->getManagerForClass(PriceAttributeProductPrice::class)
            ->getRepository(PriceAttributeProductPrice::class)
            ->findBy(['product' => $product, 'priceList' => $priceLists]);

        return $result ?? [];
    }
}

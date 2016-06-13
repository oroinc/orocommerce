<?php

namespace OroB2B\Bundle\PricingBundle\Form\Extension;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\PricingBundle\Form\Type\ProductAttributePriceCollectionType;
use OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;

class PriceAttributesProductFormExtension extends AbstractTypeExtension
{
    const PRODUCT_PRICE_ATTRIBUTES_PRICES = 'productPriceAttributesPrices';

    /**
     * @var ObjectManager
     */
    protected $om;

    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ProductType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(self::PRODUCT_PRICE_ATTRIBUTES_PRICES, 'collection', [
            'mapped' => false,
            'type' => ProductAttributePriceCollectionType::NAME,
            'label' => false,
            'required' => false
        ]);

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPreSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        /** @var Product $product */
        $product = $event->getData();

        $neededPrices = $this->getAvailablePricesForProduct($product);
        $existingPrices = $this->getProductExistingPrices($product);

        $formData = [];
        foreach ($existingPrices as $price) {
            $formData[$price->getPriceList()->getId()][] = $price;
            $neededPricesKey = array_search([
                'attribute' => $price->getPriceList(),
                'currency' => $price->getPrice()->getCurrency(),
                'unit' => $price->getUnit()
            ], $neededPrices, false);
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

    /**
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        // TODO: BB-3337 Implement price attribute prices persistence
    }

    /**
     * @param array $newInstanceData
     * @param Product $product
     * @return ProductPrice
     */
    protected function createPrice(array $newInstanceData, Product $product)
    {
        return (new PriceAttributeProductPrice())
            ->setUnit($newInstanceData['unit'])
            ->setProduct($product)
            ->setPrice(Price::create(null, $newInstanceData['currency']))
            ->setQuantity(1)
            ->setPriceList($newInstanceData['attribute']);
    }

    /**
     * @return ObjectManager|null
     */
    protected function getOM()
    {
        if (!$this->om) {
            $this->om = $this->registry->getManagerForClass(Product::class);
        }
        return $this->om;
    }

    /**
     * @param Product $product
     * @return array
     */
    protected function getAvailablePricesForProduct(Product $product)
    {
        $neededPrices = [];
        $unites = $product->getAvailableUnits();
        $priceAttributes = $this->getOM()
            ->getRepository('OroB2BPricingBundle:PriceAttributePriceList')
            ->findAll();

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
    protected function getProductExistingPrices($product)
    {
        return $this->getOM()->getRepository('OroB2BPricingBundle:PriceAttributeProductPrice')
            ->findBy(['product' => $product]);
    }
}

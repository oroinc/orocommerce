<?php

namespace OroB2B\Bundle\PricingBundle\Form\Extension;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\PricingBundle\Form\Type\ProductAttributePriceCollectionType;

class PriceAttributesProductFormExtension extends AbstractTypeExtension
{
    const PRODUCT_PRICE_ATTRIBUTES_PRICES = 'productPriceAttributesPrices';

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
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        $product = $event->getData();

        $om = $this->registry->getManagerForClass(Product::class);
        $prices = $om->getRepository('OroB2BPricingBundle:PriceAttributeProductPrice')
            ->findBy(['product' => $product]);

        $data = [];
        foreach ($prices as $price) {
            $data[$price->getPriceList()->getName()][] = $price;
        }
        $event->getForm()->get(self::PRODUCT_PRICE_ATTRIBUTES_PRICES)->setData($data);
    }
}

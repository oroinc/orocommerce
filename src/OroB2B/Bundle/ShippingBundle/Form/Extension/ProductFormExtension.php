<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;
use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use OroB2B\Bundle\ShippingBundle\Form\Type\ProductShippingOptionsCollectionType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ProductFormExtension extends AbstractTypeExtension
{
    const FORM_ELEMENT_NAME = 'product_shipping_options';

    /**
     * @var ManagerRegistry
     */
    protected $registry;
    /** @var string */
    protected $productShippingOptionsClass = 'OroB2BShippingBundle:ProductShippingOptions';

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Product $product */
        $product = $builder->getData();

        $builder->add(
            'product_shipping_options',
            ProductShippingOptionsCollectionType::NAME,
            [
                'label' => 'orob2b.shipping.productshippingoptions.entity_plural_label',
                'required' => false,
                'mapped' => false,
                'options' => [
                    'product' => $product,
                ],
            ]
        );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 10);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSetData(FormEvent $event)
    {
        /** @var Product|null $product */
        $product = $event->getData();
        if (!$product || !$product->getId()) {
            return;
        }

        $shippingOptions = $this->getProductShippingOptionsRepository()->findBy(['product' => $product->getId()]);

        $event->getForm()->get(static::FORM_ELEMENT_NAME)->setData($shippingOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSubmit(FormEvent $event)
    {
        /** @var Product|null $product */
        $product = $event->getData();
        if (!$product) {
            return;
        }

        $form = $event->getForm();
        /** @var ProductShippingOptions[] $options */
        $options = (array) $form->get(static::FORM_ELEMENT_NAME)->getData();
        foreach ($options as $option) {
            $option->setProduct($product);
        }

        if (!$form->isValid()) {
            return;
        }

        $entityManager = $this->registry->getManagerForClass($this->productShippingOptionsClass);

        // persist existing prices
        $persistedOptionsIds = [];
        foreach ($options as $option) {
            $priceId = $option->getId();
            if ($priceId) {
                $persistedOptionsIds[] = $priceId;
            }
            $entityManager->persist($option);
        }

        // remove deleted prices
        if ($product->getId()) {
            /** @var ProductShippingOptions[] $existingOptions */
            $existingOptions = $this->getProductShippingOptionsRepository()->findBy(['product' => $product->getId()]);
            foreach ($existingOptions as $option) {
                if (!in_array($option->getId(), $persistedOptionsIds, true)) {
                    $entityManager->remove($option);
                }
            }
        }
    }

    /**
     * @return ObjectRepository
     */
    protected function getProductShippingOptionsRepository()
    {
        return $this->registry->getManagerForClass('OroB2BShippingBundle:ProductShippingOptions')
            ->getRepository('OroB2BShippingBundle:ProductShippingOptions');
    }

    /**
     * Returns the name of the type being extended.
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType()
    {
        return ProductType::NAME;
    }
}

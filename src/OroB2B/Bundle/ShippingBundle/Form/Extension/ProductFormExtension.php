<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;
use OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use OroB2B\Bundle\ShippingBundle\Form\Type\ProductShippingOptionsCollectionType;
use OroB2B\Bundle\ShippingBundle\Validator\Constraints\UniqueProductUnitShippingOptions;

class ProductFormExtension extends AbstractTypeExtension
{
    const FORM_ELEMENT_NAME = 'product_shipping_options';

    /** @var ManagerRegistry */
    protected $registry;

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
            self::FORM_ELEMENT_NAME,
            ProductShippingOptionsCollectionType::NAME,
            [
                'label' => 'orob2b.shipping.product_shipping_options.entity_plural_label',
                'required' => false,
                'mapped' => false,
                'constraints' => [new UniqueProductUnitShippingOptions()],
                'options' => [
                    'product' => $product,
                ],
            ]
        );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
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

        $shippingOptions = $this->getProductShippingOptionsRepository()
            ->findBy(['product' => $product], ['productUnit' => 'ASC']);

        $event->getForm()->get(self::FORM_ELEMENT_NAME)->setData($shippingOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function onPreSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        $product = $form->getData();
        if (!$product || !$product->getId()) {
            return;
        }

        $data = $event->getData();
        $options = array_key_exists(self::FORM_ELEMENT_NAME, $data) ? $data[self::FORM_ELEMENT_NAME] : [];

        /** @var ProductUnitHolderInterface[] $existingOptions */
        $existingOptions = $form->get(self::FORM_ELEMENT_NAME)->getData();
        $newOptions = [];

        foreach ($options as $key => $option) {
            $found = false;

            foreach ($existingOptions as $existingOptionKey => $existingOption) {
                if ($existingOption->getProductUnitCode() === $option['productUnit']) {
                    $newOptions[$existingOptionKey] = $option;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $newOptions[$key] = $option;
            }
        }

        $data[self::FORM_ELEMENT_NAME] = $newOptions;
        $event->setData($data);
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
        $options = (array)$form->get(self::FORM_ELEMENT_NAME)->getData();
        foreach ($options as $option) {
            $option->setProduct($product);
        }

        if (!$form->isValid()) {
            return;
        }

        $entityManager = $this->getProductShippingOptionsObjectManager();

        $persistedOptionIds = [];
        foreach ($options as $option) {
            $optionId = $option->getId();
            if ($optionId) {
                $persistedOptionIds[] = $optionId;
            }

            $option->updateWeight();
            $option->updateDimensions();

            $entityManager->persist($option);
        }

        if ($product->getId()) {
            $existingOptions = $this->getProductShippingOptionsRepository()
                ->findBy(['product' => $product], ['productUnit' => 'ASC']);

            /** @var ProductShippingOptions[] $existingOptions */
            foreach ($existingOptions as $existingOption) {
                if (!in_array($existingOption->getId(), $persistedOptionIds, true)) {
                    $entityManager->remove($existingOption);
                }
            }
        }
    }

    /**
     * @return ObjectManager
     */
    protected function getProductShippingOptionsObjectManager()
    {
        return $this->registry->getManagerForClass('OroB2BShippingBundle:ProductShippingOptions');
    }

    /**
     * @return ObjectRepository
     */
    protected function getProductShippingOptionsRepository()
    {
        return $this->getProductShippingOptionsObjectManager()
            ->getRepository('OroB2BShippingBundle:ProductShippingOptions');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ProductType::NAME;
    }
}

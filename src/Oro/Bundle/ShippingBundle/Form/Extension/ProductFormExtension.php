<?php

namespace Oro\Bundle\ShippingBundle\Form\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use Oro\Bundle\SecurityBundle\Form\FieldAclHelper;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Form\Type\ProductShippingOptionsCollectionType;
use Oro\Bundle\ShippingBundle\Validator\Constraints\UniqueProductUnitShippingOptions;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Adds product_shipping_options field for product form.
 */
class ProductFormExtension extends AbstractTypeExtension
{
    public const FORM_ELEMENT_NAME = 'product_shipping_options';

    public function __construct(private ManagerRegistry $registry, private FieldAclHelper $fieldAclHelper)
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Product $product */
        $product = $builder->getData();

        $builder->add(
            self::FORM_ELEMENT_NAME,
            ProductShippingOptionsCollectionType::class,
            [
                'label' => 'oro.shipping.product_shipping_options.entity_plural_label',
                'required' => false,
                'mapped' => false,
                'constraints' => [new UniqueProductUnitShippingOptions()],
                'entry_options' => [
                    'product' => $product,
                ]
            ]
        );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit'], -20);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 10);
    }

    public function onPreSetData(FormEvent $event): void
    {
        if (!$this->fieldAclHelper->isFieldAclEnabled(Product::class)) {
            return;
        }

        $isUnitGranted = $this->fieldAclHelper->isFieldModificationGranted($event->getData(), 'unitPrecisions');
        FormUtils::replaceFieldOptionsRecursive(
            $event->getForm(),
            self::FORM_ELEMENT_NAME,
            ['allow_add' => $isUnitGranted, 'allow_delete' => $isUnitGranted, 'check_field_name' => 'unitPrecisions']
        );
    }

    public function onPostSetData(FormEvent $event)
    {
        $form = $event->getForm();
        /** @var Product|null $product */
        $product = $event->getData();
        if (!$product || !$product->getId()) {
            return;
        }

        if (!$form->has(self::FORM_ELEMENT_NAME)) {
            return;
        }

        $shippingOptions = $this->getProductShippingOptionsRepository()
            ->findBy(['product' => $product], ['productUnit' => 'ASC']);

        $form->get(self::FORM_ELEMENT_NAME)->setData($shippingOptions);
    }

    public function onPreSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        $product = $form->getData();
        if (!$product || !$product->getId()) {
            return;
        }

        if (!$form->has(self::FORM_ELEMENT_NAME)) {
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

    public function onPostSubmit(FormEvent $event)
    {
        /** @var Product|null $product */
        $product = $event->getData();
        if (!$product) {
            return;
        }

        $form = $event->getForm();
        if (!$form->has(self::FORM_ELEMENT_NAME)) {
            return;
        }

        /** @var ProductShippingOptions[] $options */
        $options = (array)$form->get(self::FORM_ELEMENT_NAME)->getData();
        foreach ($options as $option) {
            $option->setProduct($product);
        }

        if (!$form->isValid()) {
            return;
        }

        $this->processShippingOptions($options, $product);
    }

    private function processShippingOptions(array $options, Product $product): void
    {
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
        return $this->registry->getManagerForClass(ProductShippingOptions::class);
    }

    /**
     * @return ObjectRepository
     */
    protected function getProductShippingOptionsRepository()
    {
        return $this->getProductShippingOptionsObjectManager()
            ->getRepository(ProductShippingOptions::class);
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [ProductType::class];
    }
}

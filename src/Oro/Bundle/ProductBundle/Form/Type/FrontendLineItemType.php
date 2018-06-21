<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Type\Traits\ProductAwareTrait;
use Oro\Bundle\ProductBundle\Visibility\ProductUnitFieldsSettingsInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for line item on frontend
 */
class FrontendLineItemType extends AbstractType
{
    use ProductAwareTrait;

    const NAME = 'oro_product_frontend_line_item';

    const UNIT_FILED_NAME = 'unit';

    /**
     * @var ProductUnitFieldsSettingsInterface
     */
    private $productUnitFieldsSettings;

    /**
     * @param ProductUnitFieldsSettingsInterface $productUnitFieldsSettings
     */
    public function __construct(ProductUnitFieldsSettingsInterface $productUnitFieldsSettings)
    {
        $this->productUnitFieldsSettings = $productUnitFieldsSettings;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                self::UNIT_FILED_NAME,
                ProductUnitSelectionType::NAME,
                [
                    'required' => true,
                    'label' => 'oro.product.lineitem.unit.label',
                    'product_holder' => $builder->getData(),
                    'sell' => true,
                ]
            )
            ->add(
                'quantity',
                QuantityType::NAME,
                [
                    'required' => true,
                    'label' => 'oro.product.lineitem.quantity.enter',
                    'attr' => [
                        'placeholder' => 'oro.product.lineitem.quantity.placeholder',
                    ],
                    'product_holder' => $builder->getData(),
                    'product_unit_field' => 'unit',
                ]
            );
    }

    /**
     * This method was saved for backward compatibility, if someone extended this form type.
     *
     * @deprecated since 1.6. Unit field options are controlled via js now.
     *
     * @param FormEvent $event
     */
    public function checkUnitSelectionVisibility(FormEvent $event)
    {
        $formParent = $event->getForm();

        $form = $formParent->get(self::UNIT_FILED_NAME);
        $options = $form->getConfig()->getOptions();

        $product = $this->getProduct($form);

        if ($product && !$this->productUnitFieldsSettings->isProductUnitSelectionVisible($product)) {
            $formParent->add(
                self::UNIT_FILED_NAME,
                EntityIdentifierType::class,
                [
                    'class' => ProductUnit::class,
                    'multiple' => false,
                    'required' => $options['required'],
                    'label' => $options['label'],
                    'data' => $product->getPrimaryUnitPrecision()->getUnit(),
                    'data_class' => null,
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => ['add_product'],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}

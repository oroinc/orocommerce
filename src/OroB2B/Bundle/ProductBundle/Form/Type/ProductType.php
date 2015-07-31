<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;

use OroB2B\Bundle\FallbackBundle\Form\Type\LocalizedFallbackValueCollectionType;

class ProductType extends AbstractType
{
    const NAME = 'orob2b_product';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('sku', 'text', ['required' => true, 'label' => 'orob2b.product.sku.label'])
            ->add(
                'status',
                'oro_enum_select',
                [
                    'label'     => 'orob2b.product.status.label',
                    'enum_code' => 'prod_status',
                    'configs'   => [
                        'allowClear' => false,
                    ]
                ]
            )
            ->add(
                'inventoryStatus',
                'oro_enum_select',
                [
                    'label'     => 'orob2b.product.inventory_status.label',
                    'enum_code' => 'prod_inventory_status',
                    'configs'   => [
                        'allowClear' => false,
                    ]
                ]
            )
            ->add(
                'names',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label' => 'orob2b.product.names.label',
                    'required' => true,
                    'options' => ['constraints' => [new NotBlank()]],
                ]
            )
            ->add(
                'descriptions',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label' => 'orob2b.product.descriptions.label',
                    'required' => false,
                    'field' => 'text',
                    'type' => OroRichTextType::NAME,
                    'options' => [
                        'wysiwyg_options' => [
                            'width' => 350,
                            'height' => 250,
                        ]
                    ]
                ]
            )
            ->add(
                'image',
                'oro_image',
                [
                    'label'    => 'orob2b.product.image.label',
                    'required' => false
                ]
            )
            ->add(
                'visibility',
                'oro_enum_select',
                [
                    'label'     => 'orob2b.product.visibility.label',
                    'enum_code' => 'prod_visibility',
                    'configs'   => [
                        'allowClear' => false,
                    ]
                ]
            )
            ->add(
                'unitPrecisions',
                ProductUnitPrecisionCollectionType::NAME,
                [
                    'label'    => 'orob2b.product.unit_precisions.label',
                    'tooltip'  => 'orob2b.product.form.tooltip.unit_precision',
                    'required' => false
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'           => $this->dataClass,
            'intention'            => 'product',
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}

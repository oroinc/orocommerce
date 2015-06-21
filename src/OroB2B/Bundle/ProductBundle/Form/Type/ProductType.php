<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryTreeType;

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
            ->add('category', CategoryTreeType::NAME, ['required' => false, 'label' => 'orob2b.product.category.label'])
            ->add(
                'inventoryStatus',
                'oro_enum_select',
                [
                    'label'     => 'orob2b.product.inventory_status.label',
                    'enum_code' => 'prod_inventory_status',
                    'configs' => [
                        'allowClear' => false,
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
                    'configs' => [
                        'allowClear' => false,
                    ]
                ]
            )
            ->add(
                'unitPrecisions',
                ProductUnitPrecisionCollectionType::NAME,
                [
                    'label' => 'orob2b.product.unit_precisions.label',
                    'tooltip' => 'orob2b.product.form.tooltip.unit_precision',
                    'required' => false
                ]
            );
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
            'intention' => 'product',
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

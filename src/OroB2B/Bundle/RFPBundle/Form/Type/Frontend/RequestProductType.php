<?php

namespace OroB2B\Bundle\RFPBundle\Form\Type\Frontend;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductRemovedSelectType;
use OroB2B\Bundle\RFPBundle\Form\Type\RequestProductItemCollectionType;
use OroB2B\Bundle\RFPBundle\Form\Type\RequestProductType as BaseRequestProductType;

class RequestProductType extends AbstractType
{
    const NAME = 'orob2b_rfp_frontend_request_product';

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
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('product', ProductRemovedSelectType::NAME, [
                'required' => true,
                'label' => 'orob2b.product.entity_label',
                'create_enabled' => false,
                'grid_name' => 'products-select-grid-frontend',
                'grid_widget_route' => 'orob2b_frontend_datagrid_widget',
                'configs' => [
                    'route_name' => 'orob2b_frontend_autocomplete_search'
                ]
            ])
            ->add('requestProductItems', RequestProductItemCollectionType::NAME, [
                'label' => 'orob2b.rfp.requestproductitem.entity_plural_label',
                'options' => [
                    'compact_units' => $options['compact_units'],
                ],
            ])
            ->add('comment', 'textarea', [
                'required' => false,
                'label' => 'orob2b.rfp.requestproduct.notes.label',
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
            'intention'  => 'rfp_frontend_request_product',
        ]);
    }

    public function getParent()
    {
        return BaseRequestProductType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}

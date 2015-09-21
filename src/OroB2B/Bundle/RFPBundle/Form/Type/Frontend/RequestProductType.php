<?php

namespace OroB2B\Bundle\RFPBundle\Form\Type\Frontend;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductRemovedSelectType;
use OroB2B\Bundle\RFPBundle\Form\Type\RequestProductItemCollectionType;

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
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
            'page_component' => 'oroui/js/app/components/view-component',
            'page_component_options' => ['view' => 'orob2brfp/js/app/views/line-item-view'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['page_component'] = $options['page_component'];
        $view->vars['page_component_options'] = $options['page_component_options'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}

<?php

namespace Oro\Bundle\RFPBundle\Form\Type\Frontend;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestProductItemCollectionType;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductType as BaseRequestProductType;

class RequestProductType extends AbstractType
{
    const NAME = 'oro_rfp_frontend_request_product';

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
            ->add('product', ProductSelectType::NAME, [
                'required' => true,
                'label' => 'oro.product.entity_label',
                'create_enabled' => false,
                'grid_name' => 'products-select-grid-frontend',
                'grid_widget_route' => 'oro_frontend_datagrid_widget',
                'grid_view_widget_route' => 'oro_frontend_datagrid_widget',
                'configs' => [
                    'route_name' => 'oro_frontend_autocomplete_search'
                ],
                'data_parameters' => [
                    'scope' => 'rfp',
                    'price_list' => 'default_account_user'
                ]
            ])
            ->add('requestProductItems', RequestProductItemCollectionType::NAME, [
                'label' => 'oro.rfp.requestproductitem.entity_plural_label',
                'options' => [
                    'compact_units' => $options['compact_units'],
                ],
            ])
            ->add('comment', 'textarea', [
                'required' => false,
                'label' => 'oro.rfp.requestproduct.notes.label',
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
            'skipLoadingMask' => true
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['componentOptions']['skipLoadingMask'] = $options['skipLoadingMask'];
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return BaseRequestProductType::NAME;
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

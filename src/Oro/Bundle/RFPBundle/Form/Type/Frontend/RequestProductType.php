<?php

namespace Oro\Bundle\RFPBundle\Form\Type\Frontend;

use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductType as BaseRequestProductType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            ->add('product', ProductSelectType::class, [
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
                    'price_list' => 'default_customer_user'
                ]
            ])
            ->add('requestProductItems', RequestProductItemCollectionType::class, [
                'label' => 'oro.rfp.requestproductitem.entity_plural_label',
                'entry_options' => [
                    'compact_units' => $options['compact_units'],
                ],
            ])
            ->add('comment', TextareaType::class, [
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
            'csrf_token_id' => 'rfp_frontend_request_product',
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
        return BaseRequestProductType::class;
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

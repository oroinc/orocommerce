<?php

namespace Oro\Bundle\RFPBundle\Form\Type\Frontend;

use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type that represents an RFP request product.
 */
class RequestProductType extends AbstractType
{
    private EventSubscriberInterface $requestProductProductListener;

    private EventSubscriberInterface $requestProductItemChecksumListener;

    public function __construct(
        EventSubscriberInterface $requestProductProductListener,
        EventSubscriberInterface $requestProductItemChecksumListener
    ) {
        $this->requestProductProductListener = $requestProductProductListener;
        $this->requestProductItemChecksumListener = $requestProductItemChecksumListener;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                $builder->create(
                    'product',
                    ProductSelectType::class,
                    [
                        'required' => true,
                        'create_enabled' => false,
                        'autocomplete_alias' => 'oro_rfp_product_visibility_limited',
                        'grid_name' => 'products-select-grid-frontend',
                        'grid_widget_route' => 'oro_frontend_datagrid_widget',
                        'grid_view_widget_route' => 'oro_frontend_datagrid_widget',
                        'configs' => [
                            'route_name' => 'oro_frontend_autocomplete_search',
                            'placeholder' => 'oro.product.form.choose',
                            'result_template_twig' => '@OroProduct/Product/Autocomplete/result.html.twig',
                            'selection_template_twig' => '@OroProduct/Product/Autocomplete/selection.html.twig',
                        ],
                        'data_parameters' => [
                            'scope' => 'rfp',
                            'price_list' => 'default_customer_user',
                        ],
                    ]
                )
                    ->addEventSubscriber($this->requestProductProductListener)
            )
            ->add(
                'kitItemLineItems',
                RequestProductKitItemLineItemCollectionType::class,
                ['entry_options' => ['set_default_data' => false]]
            )
            ->add('requestProductItems', RequestProductItemCollectionType::class, ['required' => true])
            ->add('comment', TextareaType::class, [
                'required' => false,
            ]);

        $builder->addEventSubscriber($this->requestProductItemChecksumListener);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RequestProduct::class,
        ]);
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $product = $form->getData()?->getProduct();

        $view->vars['product_units'] = $product?->getAvailableUnitsPrecision() ?? [];
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_rfp_frontend_request_product';
    }
}

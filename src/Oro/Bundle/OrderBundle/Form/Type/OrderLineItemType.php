<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type representing {@see OrderLineItem}.
 */
class OrderLineItemType extends AbstractOrderLineItemType
{
    private ProductUnitsProvider $productUnitsProvider;

    private EventSubscriberInterface $orderLineItemProductListener;

    private EventSubscriberInterface $orderLineItemChecksumListener;

    public function __construct(
        ProductUnitsProvider $productUnitsProvider,
        EventSubscriberInterface $orderLineItemProductListener,
        EventSubscriberInterface $orderLineItemChecksumListener
    ) {
        $this->productUnitsProvider = $productUnitsProvider;
        $this->orderLineItemProductListener = $orderLineItemProductListener;
        $this->orderLineItemChecksumListener = $orderLineItemChecksumListener;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add(
                $builder
                    ->create(
                        'product',
                        ProductSelectType::class,
                        [
                            'autocomplete_alias' => 'oro_order_product_visibility_limited',
                            'grid_name' => 'products-select-grid',
                            'grid_parameters' => [
                                'types' => [Product::TYPE_SIMPLE, Product::TYPE_KIT]
                            ],
                            'required' => true,
                            'label' => 'oro.product.entity_label',
                            'create_enabled' => false,
                            'data_parameters' => [
                                'scope' => 'order',
                            ],
                        ]
                    )
                    ->addEventSubscriber($this->orderLineItemProductListener)
            )
            ->add(
                'kitItemLineItems',
                OrderProductKitItemLineItemCollectionType::class,
                [
                    'required' => false,
                    'currency' => $options['currency'],
                ]
            )
            ->add(
                'productSku',
                TextType::class,
                [
                    'required' => false,
                    'label' => 'oro.product.sku.label',
                ]
            )
            ->add(
                'freeFormProduct',
                TextType::class,
                [
                    'required' => false,
                    'label' => 'oro.product.entity_label',
                ]
            )
            ->add(
                'price',
                OrderPriceType::class,
                [
                    'error_bubbling' => false,
                    'required' => true,
                    'label' => 'oro.order.orderlineitem.price.label',
                    'hide_currency' => true,
                    'default_currency' => $options['currency'],
                ]
            )
            ->add('priceType', HiddenType::class, [
                'data' => PriceTypeAwareInterface::PRICE_TYPE_UNIT,
            ]);

        $builder->addEventSubscriber($this->orderLineItemChecksumListener);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault(
            'page_component_options',
            [
                'view' => 'oroorder/js/app/views/line-item-view',
                'freeFormUnits' => $this->getFreeFormUnits(),
            ]
        );
    }

    protected function getFreeFormUnits(): array
    {
        return $this->productUnitsProvider->getAvailableProductUnitsWithPrecision();
    }


    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_order_line_item';
    }

    #[\Override]
    protected function updateAvailableUnits(FormInterface $form): void
    {
        /** @var OrderLineItem $item */
        $item = $form->getData();
        if (!$item->getProduct()) {
            return;
        }

        FormUtils::replaceField($form, 'productUnit');
    }
}

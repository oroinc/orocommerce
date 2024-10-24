<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Provider\DiscountSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for Order Discount Collection.
 */
class OrderDiscountCollectionTableType extends AbstractType
{
    const NAME = 'oro_order_discount_collection_table';

    #[\Override]
    public function getParent(): ?string
    {
        return OrderCollectionTableType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('order');
        $resolver->setAllowedTypes('order', Order::class);

        $resolver->setDefaults(
            [
                'template_name' => '@OroOrder/Discount/order_discount_collection.html.twig',
                'page_component' => 'oroui/js/app/components/view-component',
                'page_component_options' => [
                    'view' => 'oroorder/js/app/views/discount-collection-view',
                    'discountType' => DiscountSubtotalProvider::TYPE,
                    'totalType' => LineItemSubtotalProvider::TYPE,
                    'percentType' => OrderDiscount::TYPE_PERCENT
                ],
                'attr' => ['class' => 'oro-discount-collection'],
                'entry_type' => OrderDiscountCollectionRowType::class
            ]
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['order'] = $options['order'];
    }
}

<?php

namespace Oro\Bundle\CheckoutBundle\Form\Type;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemsShippingManager;
use Oro\Bundle\FormBundle\Form\DataTransformer\ArrayToJsonTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Represents form type for line items shipping method.
 */
class LineItemShippingMethodsType extends AbstractType
{
    const NAME = 'oro_checkout_line_items_shipping_methods';

    private CheckoutLineItemsShippingManager $shippingManager;

    public function __construct(CheckoutLineItemsShippingManager $shippingManager)
    {
        $this->shippingManager = $shippingManager;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix()
    {
        return self::NAME;
    }

    public function getParent()
    {
        return HiddenType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['checkout'])
            ->setDefaults([
                'data_class' => null,
                'checkout' => null
            ])
            ->setAllowedTypes('checkout', Checkout::class);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmit']);
        $builder->addViewTransformer(new ArrayToJsonTransformer());
    }

    /**
     * Update each checkout line item with selected shipping methods.
     *
     * @param FormEvent $event
     */
    public function onSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        $options = $form->getConfig()->getOptions();
        /** @var Checkout $checkout */
        $checkout = $options['checkout'];

        if (!$checkout) {
            return;
        }

        $this->shippingManager->updateLineItemsShippingMethods($data, $checkout);
    }
}

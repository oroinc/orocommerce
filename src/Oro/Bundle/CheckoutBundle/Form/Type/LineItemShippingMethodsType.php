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
 * The form type for line items shipping method.
 */
class LineItemShippingMethodsType extends AbstractType
{
    private CheckoutLineItemsShippingManager $shippingManager;

    public function __construct(CheckoutLineItemsShippingManager $shippingManager)
    {
        $this->shippingManager = $shippingManager;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(new ArrayToJsonTransformer());
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            // update each checkout line item with selected shipping methods
            $options = $event->getForm()->getConfig()->getOptions();
            /** @var Checkout|null $checkout */
            $checkout = $options['checkout'];
            if (null !== $checkout) {
                $this->shippingManager->updateLineItemsShippingMethods($event->getData(), $checkout);
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['checkout']);
        $resolver->setDefault('data_class', null);
        $resolver->setDefault('checkout', null);
        $resolver->setAllowedTypes('checkout', Checkout::class);
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_checkout_line_items_shipping_methods';
    }

    /**
     * {@inheritDoc}
     */
    public function getParent(): string
    {
        return HiddenType::class;
    }
}

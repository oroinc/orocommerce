<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Form\Type;

use Oro\Bundle\AddressValidationBundle\Form\Type\Frontend\FrontendAddressValidatedAtType;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Represents a "validatedAt" form field used in the address form on checkout on storefront.
 */
class CheckoutAddressValidatedAtType extends AbstractType
{
    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['checkoutId'] = $options['checkout']->getId();
        $view->vars['isBillingAddressValid'] = (bool)$options['checkout']->getBillingAddress()?->getValidatedAt();
        $view->vars['addressType'] = $options['address_type'];

        $rootBlockPrefix = $form->getRoot()->getConfig()->getOption('block_prefix', $form->getRoot()->getName());
        array_splice($view->vars['block_prefixes'], -1, 0, $rootBlockPrefix . '__' . $this->getBlockPrefix());
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->define('checkout')
            ->required()
            ->allowedTypes(Checkout::class);

        $resolver
            ->define('address_type')
            ->required()
            ->allowedTypes('string');
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_checkout_address_validated_at';
    }

    #[\Override]
    public function getParent(): string
    {
        return FrontendAddressValidatedAtType::class;
    }
}

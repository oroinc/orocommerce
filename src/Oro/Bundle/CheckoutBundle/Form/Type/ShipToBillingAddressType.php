<?php

namespace Oro\Bundle\CheckoutBundle\Form\Type;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ShipToBillingAddressType extends AbstractType
{
    public const NAME = 'oro_ship_to_billing_address';
    public const SHIPPING_ADDRESS_FORM_FIELD = 'shipping_address';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
    }

    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $parent = $form->getParent();

        if ($event->getData() && $parent->has(self::SHIPPING_ADDRESS_FORM_FIELD)) {
            FormUtils::replaceField($parent, self::SHIPPING_ADDRESS_FORM_FIELD, [], ['constraints']);
        }
    }

    #[\Override]
    public function getParent(): ?string
    {
        return CheckboxType::class;
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
}

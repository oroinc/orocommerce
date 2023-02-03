<?php

namespace Oro\Bundle\CheckoutBundle\Form\Type;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\FrontendBundle\Form\Type\CountryType;
use Oro\Bundle\FrontendBundle\Form\Type\RegionType;
use Oro\Bundle\OrderBundle\Form\Type\OrderAddressType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Represents checkout address form type
 */
class CheckoutAddressType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('customerAddress', CheckoutAddressSelectType::class, [
            'object' => $options['object'],
            'address_type' => $options['addressType'],
            'required' => true,
            'mapped' => false,
        ]);

        $builder->add('country', CountryType::class, [
            'required' => true,
            'label' => 'oro.address.country.label',
        ]);

        $builder->add('region', RegionType::class, [
            'required' => true,
            'label' => 'oro.address.region.label',
        ]);

        $builder->get('city')->setRequired(true);
        $builder->get('postalCode')->setRequired(true);
        $builder->get('street')->setRequired(true);
        $builder->get('customerAddress')->setRequired(true);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $orderAddress = $event->getData();
            // The check for ENTER_MANUALLY on customerAddress field is not made here because it does not work for
            // single page checkout. Instead, we check for customer / customer user address relation.
            if ($orderAddress && ($orderAddress->getCustomerAddress() || $orderAddress->getCustomerUserAddress())) {
                // Clears the address fields because if user chooses to enter address manually, these fields should be
                // shown empty.
                $event->setData(null);
            }
        }, 100);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setAllowedTypes('object', Checkout::class)
            ->addNormalizer('disabled', function ($options, $value) {
                if (null === $value) {
                    return false;
                }

                return $value;
            });
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_checkout_address';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return OrderAddressType::class;
    }
}

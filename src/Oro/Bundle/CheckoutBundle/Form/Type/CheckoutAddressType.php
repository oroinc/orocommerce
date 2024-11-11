<?php

namespace Oro\Bundle\CheckoutBundle\Form\Type;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\FrontendBundle\Form\Type\CountryType;
use Oro\Bundle\FrontendBundle\Form\Type\RegionType;
use Oro\Bundle\OrderBundle\Form\Type\OrderAddressType;
use Oro\Component\Layout\Extension\Theme\Model\CurrentThemeProvider;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
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
    public const string ENTER_MANUALLY = '0';

    public function __construct(
        private CurrentThemeProvider $currentThemeProvider,
        private ThemeManager $themeManager
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
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

        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData'], 100)
            ->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit'], 100);
    }

    public function onPreSetData(FormEvent $event): void
    {
        $orderAddress = $event->getData();
        // The check for ENTER_MANUALLY on customerAddress field is not made here because it does not work for
        // single page checkout. Instead, we check for customer / customer user address relation.
        if ($orderAddress && ($orderAddress->getCustomerAddress() || $orderAddress->getCustomerUserAddress())) {
            // Clears the address fields because if user chooses to enter address manually, these fields should be
            // shown empty.
            $event->setData(null);
        }
    }

    public function onPreSubmit(FormEvent $event): void
    {
        if ($this->isOldTheme()) {
            return;
        }

        $address = $event->getData()['customerAddress'] ?? null;
        if ($address === self::ENTER_MANUALLY && $event->getForm()->getConfig()->getOption('multiStepCheckout')) {
            foreach ($event->getForm()->all() as $child) {
                FormUtils::replaceFieldOptionsRecursive($event->getForm(), $child->getName(), ['mapped' => false]);
            }
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('multiStepCheckout', false)
            ->setAllowedTypes('object', Checkout::class)
            ->setAllowedTypes('multiStepCheckout', 'bool')
            ->addNormalizer('disabled', function ($options, $value) {
                if (null === $value) {
                    return false;
                }

                return $value;
            });
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_checkout_address';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return OrderAddressType::class;
    }

    private function isOldTheme(): bool
    {
        return $this->themeManager->themeHasParent(
            $this->currentThemeProvider->getCurrentThemeId() ?? '',
            ['default_50', 'default_51']
        );
    }
}

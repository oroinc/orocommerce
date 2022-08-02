<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AddressType as AddressTypeEntity;
use Oro\Bundle\AddressBundle\Form\Type\AddressType;
use Oro\Bundle\AddressBundle\Validator\Constraints\NameOrOrganization;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\FormBundle\Form\Extension\StripTagsExtension;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Represents order address form type
 */
class OrderAddressType extends AbstractType
{
    const NAME = 'oro_order_address_type';

    /** @var OrderAddressSecurityProvider */
    private $addressSecurityProvider;

    public function __construct(OrderAddressSecurityProvider $addressSecurityProvider)
    {
        $this->addressSecurityProvider = $addressSecurityProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $addressType = $options['addressType'];
        $builder->add('customerAddress', OrderAddressSelectType::class, [
            'object' => $options['object'],
            'address_type' => $options['addressType'],
            'required' => false,
            'mapped' => false
        ]);
        $builder->add('phone', TextType::class, [
            'required' => false,
            StripTagsExtension::OPTION_NAME => true,
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            // Render previously saved address in address selector
            FormUtils::replaceFieldOptionsRecursive(
                $event->getForm(),
                'customerAddress',
                ['data' => $event->getData()]
            );
        });
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($addressType) {
            $form = $event->getForm();
            $isManualEditGranted = $this->addressSecurityProvider->isManualEditGranted($addressType);
            if (!$isManualEditGranted) {
                $event->setData(null);
            }

            $orderAddress = $event->getData();
            $selectedAddress = $form->get('customerAddress')->getData();
            if ($selectedAddress instanceof OrderAddress
                && $this->isAddressChangeRequired($selectedAddress, $orderAddress)
            ) {
                $event->setData($selectedAddress);
            }
        }, -10);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $isManualEditGranted = $this->addressSecurityProvider->isManualEditGranted($options['addressType']);
        foreach ($view->children as $child) {
            $child->vars['disabled'] = !$isManualEditGranted || $options['disabled'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['object', 'addressType'])
            ->setDefaults([
                'data_class' => OrderAddress::class,
                'constraints' => [new NameOrOrganization()],
            ])
            ->setAllowedValues('addressType', [AddressTypeEntity::TYPE_BILLING, AddressTypeEntity::TYPE_SHIPPING])
            ->setAllowedTypes('object', CustomerOwnerAwareInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return AddressType::class;
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

    private function isAddressChangeRequired(OrderAddress $selectedAddress, ?OrderAddress $orderAddress): bool
    {
        if (!$orderAddress instanceof OrderAddress) {
            return true;
        }

        if (!$orderAddress->getCustomerAddress() && !$orderAddress->getCustomerUserAddress()) {
            return true;
        }

        if ($this->isAddressChanged($selectedAddress, $orderAddress, 'Customer')) {
            return true;
        }

        if ($this->isAddressChanged($selectedAddress, $orderAddress, 'CustomerUser')) {
            return true;
        }

        return false;
    }

    private function isAddressChanged(
        OrderAddress $selectedAddress,
        OrderAddress $orderAddress,
        string $addressType
    ): bool {
        $method = 'get' . $addressType . 'Address';
        if ($selectedAddress->{$method}()) {
            if (!$orderAddress->{$method}()
                || $orderAddress->{$method}()->getId() !== $selectedAddress->{$method}()->getId()
            ) {
                return true;
            }
        }

        return false;
    }
}

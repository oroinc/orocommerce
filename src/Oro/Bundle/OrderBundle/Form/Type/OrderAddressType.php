<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AddressType as AddressTypeEntity;
use Oro\Bundle\AddressBundle\Form\Type\AddressType;
use Oro\Bundle\AddressBundle\Validator\Constraints\NameOrOrganization;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\FormBundle\Form\Extension\StripTagsExtension;
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
            'address_type' => $addressType,
            'required' => false,
            'mapped' => false,
        ]);

        $builder->add('phone', TextType::class, [
            'required' => false,
            StripTagsExtension::OPTION_NAME => true,
        ]);

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($addressType) {
            $form = $event->getForm();
            $isManualEditGranted = $this->addressSecurityProvider->isManualEditGranted($addressType);
            if (!$isManualEditGranted) {
                $event->setData(null);
            }

            $address = $form->get('customerAddress')->getData();
            if ($address instanceof OrderAddress) {
                $event->setData($address);
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
}

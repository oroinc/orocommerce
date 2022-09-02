<?php

namespace Oro\Bundle\CheckoutBundle\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Form\DataTransformer\OrderAddressToAddressIdentifierViewTransformer;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Form\Type\OrderAddressSelectType;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Manager\TypedOrderAddressCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Represents checkout address select form type with list of grouped available addresses for customer user
 */
class CheckoutAddressSelectType extends AbstractType
{
    private OrderAddressManager $addressManager;

    private OrderAddressToAddressIdentifierViewTransformer $orderAddressToAddressIdentifierViewTransforLmer;

    public function __construct(
        OrderAddressManager $addressManager,
        OrderAddressToAddressIdentifierViewTransformer $orderAddressToAddressIdentifierViewTransforLmer
    ) {
        $this->addressManager = $addressManager;
        $this->orderAddressToAddressIdentifierViewTransforLmer = $orderAddressToAddressIdentifierViewTransforLmer;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Checkout $object */
        $object = $options['object'];
        $addressType = $options['address_type'];
        /** @var TypedOrderAddressCollection $collection */
        $collection = $options['address_collection'];

        $address = $this->getSelectedAddress($object, $addressType);
        if ($address === null) {
            $address = $collection->getDefaultAddressKey();
        }

        if ($address !== null) {
            $builder->setData($address);
        }

        // Transformer must be executed before ChoiceToValueTransformer so it can transform OrderAddress to a key.
        $builder->addViewTransformer($this->orderAddressToAddressIdentifierViewTransforLmer, true);

        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmit']);
    }

    public function onSubmit(FormEvent $event): void
    {
        $options = $event->getForm()->getConfig()->getOptions();
        /** @var Checkout $object */
        $object = $options['object'];
        $addressType = $options['address_type'];
        $address = $event->getData();
        if ($address === null || $address === OrderAddressSelectType::ENTER_MANUALLY) {
            if ($addressType === AddressType::TYPE_BILLING) {
                $orderAddress = $object->getBillingAddress();
            } elseif ($addressType === AddressType::TYPE_SHIPPING) {
                $orderAddress = $object->getShippingAddress();
            }

            if (isset($orderAddress) && !$orderAddress->getCustomerAddress()
                && !$orderAddress->getCustomerUserAddress()) {
                $event->setData($orderAddress);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $labelPrefix = $options['group_label_prefix'];
        /** @var TypedOrderAddressCollection $collection */
        $collection = $options['address_collection'];
        $addressType = $options['address_type'];

        $addresses = $collection->toArray();

        $action = count($addresses) ? 'select' : 'enter';
        $view->vars['label'] = sprintf('%sform.address.%s.%s.label', $labelPrefix, $action, $addressType);

        $view->vars['attr']['data-addresses-types'] = json_encode(
            $this->addressManager->getAddressTypes($addresses, $options['group_label_prefix'])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data' => null,
                'data_class' => null,
                'group_label_prefix' => 'oro.checkout.'
            ])
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
    public function getBlockPrefix(): string
    {
        return 'oro_checkout_address_select';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return OrderAddressSelectType::class;
    }

    /**
     * @param Checkout $entity
     * @param string $type
     *
     * @return null|OrderAddress
     */
    private function getSelectedAddress(Checkout $entity, $type): ?OrderAddress
    {
        $address = null;
        if ($type === AddressType::TYPE_BILLING) {
            $address = $entity->getBillingAddress();
        } elseif ($type === AddressType::TYPE_SHIPPING) {
            $address = $entity->getShippingAddress();
        }

        return $address;
    }
}

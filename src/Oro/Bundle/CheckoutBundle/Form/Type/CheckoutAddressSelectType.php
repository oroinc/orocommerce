<?php

namespace Oro\Bundle\CheckoutBundle\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
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
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Represents checkout address select form type with list of grouped available addresses for customer user
 */
class CheckoutAddressSelectType extends AbstractType
{
    const NAME = 'oro_checkout_address_select';
    const DEFAULT_GROUP_LABEL_PREFIX = 'oro.checkout.';

    /** @var OrderAddressManager */
    private $addressManager;

    /** @var PropertyAccessor */
    private $propertyAccessor;

    /** @var array  */
    private $requiredFields = [];

    /**
     * @param OrderAddressManager $addressManager
     * @param PropertyAccessor $propertyAccessor
     * @param array $requiredFields
     */
    public function __construct(
        OrderAddressManager $addressManager,
        PropertyAccessor $propertyAccessor,
        array $requiredFields
    ) {
        $this->addressManager = $addressManager;
        $this->propertyAccessor = $propertyAccessor;
        $this->requiredFields = $requiredFields;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Checkout $object */
        $object = $options['object'];
        $addressType = $options['address_type'];
        /** @var TypedOrderAddressCollection $collection */
        $collection = $options['address_collection'];

        $selectedKey = $this->getSelectedAddress($object, $addressType);
        if ($selectedKey === null) {
            $selectedKey = $collection->getDefaultAddressKey();
        }

        if ($selectedKey !== null) {
            $builder->setData($selectedKey);
        }

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($object, $addressType) {
            $address = $event->getData();
            if ($address === OrderAddressSelectType::ENTER_MANUALLY) {
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
        });
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'data' => null,
                'data_class' => null,
                'group_label_prefix' => self::DEFAULT_GROUP_LABEL_PREFIX,
            ])
            ->setAllowedTypes('object', Checkout::class);
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

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return OrderAddressSelectType::class;
    }

    /**
     * @param Checkout $entity
     * @param string $type
     *
     * @return null|string
     */
    private function getSelectedAddress(Checkout $entity, $type)
    {
        $selectedKey = null;
        if ($type === AddressType::TYPE_BILLING) {
            $selectedKey = $this->getSelectedAddressKey($entity->getBillingAddress());
        } elseif ($type === AddressType::TYPE_SHIPPING) {
            $selectedKey = $this->getSelectedAddressKey($entity->getShippingAddress());
        }

        return $selectedKey;
    }

    /**
     * @param OrderAddress|null $checkoutAddress
     *
     * @return int|string
     */
    private function getSelectedAddressKey(OrderAddress $checkoutAddress = null)
    {
        $selectedKey = null;
        if ($checkoutAddress) {
            if ($checkoutAddress->getCustomerAddress()) {
                $selectedKey = $this->addressManager->getIdentifier($checkoutAddress->getCustomerAddress());
            } elseif ($checkoutAddress->getCustomerUserAddress()) {
                $selectedKey = $this->addressManager->getIdentifier($checkoutAddress->getCustomerUserAddress());
            } elseif (!$this->addressIsEmpty($checkoutAddress)) {
                // Select new address if it was already created by customer.
                $selectedKey = OrderAddressSelectType::ENTER_MANUALLY;
            }
        }

        return $selectedKey;
    }

    /**
     * Check if new address is fulfilled with some data. Assume address is not empty if one of the required fields
     * is not empty.
     *
     * @param OrderAddress $address
     * @return bool
     */
    private function addressIsEmpty(OrderAddress $address): bool
    {
        foreach ($this->requiredFields as $field) {
            try {
                $value = $this->propertyAccessor->getValue($address, $field);
            } catch (NoSuchPropertyException $e) {
                $value = null;
            }

            if (null !== $value) {
                return false;
            }
        }

        return true;
    }
}

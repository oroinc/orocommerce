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
use Symfony\Component\PropertyAccess\PropertyAccess;
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

    /** @var PropertyAccess */
    private $propertyAccessor;

    /** @var array  */
    private $requiredFields = [];

    /**
     * @param OrderAddressManager $addressManager
     */
    public function __construct(OrderAddressManager $addressManager)
    {
        $this->addressManager = $addressManager;
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

        $address = $this->getSelectedAddress($object, $addressType);
        if ($address === null) {
            $address = $collection->getDefaultAddressKey();
        }

        if ($address !== null) {
            $builder->setData($address);
        }

        $builder->addViewTransformer(
            $this->getViewTransformer(),
            // Transformer must be executed before ChoiceToValueTransformer so it can transform OrderAddress to a key.
            true
        );

        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmit']);
    }

    /**
     * @param FormEvent $event
     */
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
     * @param array $fields
     */
    public function setRequiredFields(array $fields)
    {
        $this->requiredFields = $fields;
    }

    /**
     * @param PropertyAccessor $propertyAccessor
     */
    public function setPropertyAccessor(PropertyAccessor $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
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

    /**
     * @return PropertyAccessor
     */
    private function getPropertyAccessor(): PropertyAccessor
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    /**
     * @return OrderAddressToAddressIdentifierViewTransformer
     */
    private function getViewTransformer(): OrderAddressToAddressIdentifierViewTransformer
    {
        return new OrderAddressToAddressIdentifierViewTransformer(
            $this->addressManager,
            $this->getPropertyAccessor(),
            $this->requiredFields
        );
    }
}

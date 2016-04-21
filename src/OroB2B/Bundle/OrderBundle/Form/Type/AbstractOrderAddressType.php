<?php

namespace OroB2B\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;

use OroB2B\Bundle\AccountBundle\Entity\AccountOwnerAwareInterface;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress;
use OroB2B\Bundle\AccountBundle\Entity\AbstractDefaultTypedAddress;
use OroB2B\Bundle\OrderBundle\Manager\OrderAddressManager;
use OroB2B\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;

abstract class AbstractOrderAddressType extends AbstractType
{

    /** @var string */
    protected $dataClass;

    /** @var AddressFormatter */
    protected $addressFormatter;

    /** @var OrderAddressManager */
    protected $orderAddressManager;

    /** @var OrderAddressSecurityProvider */
    protected $orderAddressSecurityProvider;

    /** @var Serializer */
    protected $serializer;

    /**
     * @param AddressFormatter $addressFormatter
     * @param OrderAddressManager $orderAddressManager
     * @param OrderAddressSecurityProvider $orderAddressSecurityProvider
     * @param Serializer $serializer
     */
    public function __construct(
        AddressFormatter $addressFormatter,
        OrderAddressManager $orderAddressManager,
        OrderAddressSecurityProvider $orderAddressSecurityProvider,
        Serializer $serializer
    ) {
        $this->addressFormatter = $addressFormatter;
        $this->orderAddressManager = $orderAddressManager;
        $this->orderAddressSecurityProvider = $orderAddressSecurityProvider;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $type = $options['addressType'];
        $order = $options['object'];
        $isEditEnabled = $options['isEditEnabled'];

        $isManualEditGranted = $this->orderAddressSecurityProvider->isManualEditGranted($type);
        $this->initAccountAddressField($builder, $type, $order, $isManualEditGranted, $isEditEnabled);

        $builder->add('phone', 'text', ['required' => false]);

        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) use ($isManualEditGranted) {
                if (!$isManualEditGranted) {
                    $event->setData(null);
                }

                $form = $event->getForm();
                if (!$form->has('accountAddress')) {
                    return;
                }

                $identifier = $form->get('accountAddress')->getData();
                if ($identifier === null) {
                    return;
                }

                //Enter manually or Account/AccountUser address
                $orderAddress = $event->getData();

                $address = null;
                if ($identifier) {
                    $address = $this->orderAddressManager->getEntityByIdentifier($identifier);
                }

                if ($orderAddress || $address) {
                    $event->setData($this->orderAddressManager->updateFromAbstract($address, $orderAddress));
                }
            },
            -10
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $isManualEditGranted = $this->orderAddressSecurityProvider->isManualEditGranted($options['addressType']);

        foreach ($view->children as $child) {
            $child->vars['disabled'] = !$isManualEditGranted || $options['disabled'];
        }

        if ($view->offsetExists('accountAddress')) {
            $view->offsetGet('accountAddress')->vars['disabled'] = false;
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
                'data_class' => $this->dataClass,
                'isEditEnabled' => true,
            ])
            ->setAllowedValues('addressType', [AddressType::TYPE_BILLING, AddressType::TYPE_SHIPPING])
            ->setAllowedTypes('object', 'OroB2B\Bundle\AccountBundle\Entity\AccountOwnerAwareInterface');
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @param array $addresses
     *
     * @return array
     */
    protected function getChoices(array $addresses = [])
    {
        array_walk_recursive(
            $addresses,
            function (&$item) {
                if ($item instanceof AbstractAddress) {
                    $item = $this->addressFormatter->format($item, null, ', ');
                }

                return $item;
            }
        );

        return $addresses;
    }

    /**
     * @param AccountOwnerAwareInterface $entity
     * @param string $type
     * @param array $addresses
     *
     * @return null|string
     */
    protected function getDefaultAddressKey(AccountOwnerAwareInterface $entity, $type, array $addresses)
    {
        if (!$addresses) {
            return null;
        }

        $addresses = call_user_func_array('array_merge', array_values($addresses));
        $accountUser = $entity->getAccountUser();
        $addressKey = null;

        /** @var AbstractDefaultTypedAddress $address */
        foreach ($addresses as $key => $address) {
            if ($address->hasDefault($type)) {
                $addressKey = $key;
                if ($address instanceof AccountUserAddress &&
                    $address->getFrontendOwner()->getId() === $accountUser->getId()
                ) {
                    break;
                }
            }
        }

        return $addressKey;
    }

    /**
     * @param array $addresses
     *
     * @return array
     */
    protected function getPlainData(array $addresses = [])
    {
        $data = [];

        array_walk_recursive(
            $addresses,
            function ($item, $key) use (&$data) {
                if ($item instanceof AbstractAddress) {
                    $data[$key] = $this->serializer->normalize($item);
                }
            }
        );

        return $data;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param string $type - address type
     * @param AccountOwnerAwareInterface $entity
     * @param bool $isManualEditGranted
     * @param bool $isEditEnabled
     *
     * @return bool
     */
    abstract protected function initAccountAddressField(
        FormBuilderInterface $builder,
        $type,
        AccountOwnerAwareInterface $entity,
        $isManualEditGranted,
        $isEditEnabled
    );
}

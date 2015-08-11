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

use OroB2B\Bundle\OrderBundle\Model\OrderAddressManager;
use OroB2B\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;

class OrderAddressType extends AbstractType
{
    const NAME = 'orob2b_order_address_type';

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
        $isManualEditGranted = $this->orderAddressSecurityProvider->isManualEditGranted($type);
        $addresses = $this->orderAddressManager->getGroupedAddresses($options['order'], $type);

        $accountAddressOptions = [
            'label' => false,
            'required' => false,
            'mapped' => false,
            'choices' => $this->getChoices($addresses),
            'configs' => ['placeholder' => 'orob2b.order.form.address.choose'],
            'attr' => [
                'data-addresses' => json_encode($this->getPlainData($addresses)),
            ],
        ];

        if ($isManualEditGranted) {
            $accountAddressOptions['choices'] = array_merge(
                $accountAddressOptions['choices'],
                ['orob2b.order.form.address.manual']
            );
            $accountAddressOptions['configs']['placeholder'] = 'orob2b.order.form.address.choose_or_create';
        }

        $builder->add('accountAddress', 'genemu_jqueryselect2_choice', $accountAddressOptions);

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
                if ($identifier) {
                    $address = $this->orderAddressManager->getEntityByIdentifier($identifier);
                    if ($address) {
                        $event->setData(
                            $this->orderAddressManager->updateFromAbstract($address, $event->getData())
                        );
                    }
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
            $child->vars['disabled'] = !$isManualEditGranted;
            $child->vars['required'] = false;
            unset(
                $child->vars['attr']['data-validation'],
                $child->vars['attr']['data-required'],
                $child->vars['label_attr']['data-required']
            );
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
            ->setRequired(['order', 'addressType'])
            ->setDefaults(['data_class' => $this->dataClass])
            ->setAllowedValues('addressType', [AddressType::TYPE_BILLING, AddressType::TYPE_SHIPPING])
            ->setAllowedTypes('order', 'OroB2B\Bundle\OrderBundle\Entity\Order');
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_address';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
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
}

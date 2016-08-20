<?php

namespace Oro\Bundle\SaleBundle\Form\Type;

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
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Model\QuoteAddressManager;
use Oro\Bundle\SaleBundle\Provider\QuoteAddressSecurityProvider;
use Oro\Bundle\AccountBundle\Entity\AccountUserAddress;
use Oro\Bundle\AccountBundle\Entity\AbstractDefaultTypedAddress;

class QuoteAddressType extends AbstractType
{
    const NAME = 'orob2b_quote_address_type';

    /** @var string */
    protected $dataClass;

    /** @var AddressFormatter */
    protected $addressFormatter;

    /** @var QuoteAddressManager */
    protected $quoteAddressManager;

    /** @var QuoteAddressSecurityProvider */
    protected $quoteAddressSecurityProvider;

    /** @var Serializer */
    protected $serializer;

    /**
     * @param AddressFormatter $addressFormatter
     * @param QuoteAddressManager $quoteAddressManager
     * @param QuoteAddressSecurityProvider $quoteAddressSecurityProvider
     * @param Serializer $serializer
     */
    public function __construct(
        AddressFormatter $addressFormatter,
        QuoteAddressManager $quoteAddressManager,
        QuoteAddressSecurityProvider $quoteAddressSecurityProvider,
        Serializer $serializer
    ) {
        $this->addressFormatter = $addressFormatter;
        $this->quoteAddressManager = $quoteAddressManager;
        $this->quoteAddressSecurityProvider = $quoteAddressSecurityProvider;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $type = $options['addressType'];
        $quote = $options['quote'];

        $isManualEditGranted = $this->quoteAddressSecurityProvider->isManualEditGranted($type);
        $addresses = $this->quoteAddressManager->getGroupedAddresses($quote, $type);

        $accountAddressOptions = [
            'label' => false,
            'required' => false,
            'mapped' => false,
            'choices' => $this->getChoices($addresses),
            'configs' => ['placeholder' => 'oro.quote.form.address.choose'],
            'attr' => [
                'data-addresses' => json_encode($this->getPlainData($addresses)),
                'data-default' => $this->getDefaultAddressKey($quote, $type, $addresses),
            ],
        ];

        if ($isManualEditGranted) {
            $accountAddressOptions['choices'] = array_merge(
                $accountAddressOptions['choices'],
                ['oro.sale.quote.form.address.manual']
            );
            $accountAddressOptions['configs']['placeholder'] = 'oro.sale.quote.form.address.choose_or_create';
        }

        $builder->add('accountAddress', 'genemu_jqueryselect2_choice', $accountAddressOptions);
        $builder->add('phone', 'text');

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
                $quoteAddress = $event->getData();

                $address = null;
                if ($identifier) {
                    $address = $this->quoteAddressManager->getEntityByIdentifier($identifier);
                }

                if ($quoteAddress || $address) {
                    $event->setData($this->quoteAddressManager->updateFromAbstract($address, $quoteAddress));
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
        $isManualEditGranted = $this->quoteAddressSecurityProvider->isManualEditGranted($options['addressType']);
        $exceptKey = ['phone'];

        foreach ($view->children as $key => $child) {
            if (in_array($key, $exceptKey)) {
                continue;
            }

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
            ->setRequired(['quote', 'addressType'])
            ->setDefaults(['data_class' => $this->dataClass])
            ->setAllowedValues('addressType', [ AddressType::TYPE_SHIPPING])
            ->setAllowedTypes('quote', 'Oro\Bundle\SaleBundle\Entity\Quote');
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
     * @param Quote $quote
     * @param string $type
     * @param array $addresses
     *
     * @return null|string
     */
    protected function getDefaultAddressKey(Quote $quote, $type, array $addresses)
    {
        if (!$addresses) {
            return null;
        }

        $addresses = call_user_func_array('array_merge', array_values($addresses));
        $accountUser = $quote->getAccountUser();
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
}

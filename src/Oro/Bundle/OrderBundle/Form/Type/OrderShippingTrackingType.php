<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\TrackingAwareShippingMethodsProviderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\AlreadySubmittedException;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderShippingTrackingType extends AbstractType
{
    const NAME = 'oro_order_shipping_tracking';

    /**
     * @var array
     */
    protected $tracking;

    /**
     * @var array|null
     */
    protected $choices;

    /**
     * @var TrackingAwareShippingMethodsProviderInterface|null
     */
    protected $trackingAwareShippingMethodsProvider;

    /**
     * @param TrackingAwareShippingMethodsProviderInterface $trackingAwareShippingMethodsProvider
     */
    public function __construct($trackingAwareShippingMethodsProvider = null)
    {
        $this->trackingAwareShippingMethodsProvider = $trackingAwareShippingMethodsProvider;
    }

    /**
     * @return array|null
     */
    private function getTrackingMethodsChoices()
    {
        if ($this->choices !== null) {
            return $this->choices;
        }

        $this->choices = [];
        if ($this->trackingAwareShippingMethodsProvider !== null) {
            $methods = $this->trackingAwareShippingMethodsProvider->getTrackingAwareShippingMethods();

            /** @var ShippingMethodInterface $method */
            foreach ($methods as $method) {
                $this->choices[$method->getLabel()] = $method->getIdentifier();
            }
        }
        return $this->choices;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        if ($this->getTrackingMethodsChoices()) {
            $builder
                ->add(
                    'method',
                    SelectSwitchInputType::class,
                    [
                        'required' => false,
                        'choices' => $this->getTrackingMethodsChoices(),
                        'mode' => SelectSwitchInputType::MODE_SELECT
                    ]
                );
        } else {
            $builder
                ->add(
                    'method',
                    TextType::class,
                    [
                        'required' => true
                    ]
                );
        }
        $builder
            ->add(
                'number',
                TextType::class,
                [
                    'required' => true
                ]
            )
            ->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetDataListener'])
            ->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmitDataListener']);
    }

    /**
     * @throws AlreadySubmittedException
     * @throws LogicException
     * @throws \OutOfBoundsException
     * @throws UnexpectedTypeException
     */
    public function preSetDataListener(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        if ($data instanceof OrderShippingTracking) {
            $config = $form->get('method')->getConfig();
            $options = $config->getOptions();

            if ($this->getTrackingMethodsChoices()) {
                if (null === $options['choices'] || !in_array($data->getMethod(), $options['choices'], true)) {
                    $form->add(
                        'method',
                        SelectSwitchInputType::class,
                        array_replace(
                            $options,
                            [
                                'mode' => SelectSwitchInputType::MODE_INPUT
                            ]
                        )
                    );
                }
            }
        }
    }

    /**
     * @throws AlreadySubmittedException
     * @throws LogicException
     * @throws \OutOfBoundsException
     * @throws UnexpectedTypeException
     */
    public function preSubmitDataListener(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        $config = $form->get('method')->getConfig();
        $options = $config->getOptions();

        if ($this->getTrackingMethodsChoices()) {
            if (null === $options['choices'] || !in_array($data['method'], $options['choices'], true)) {
                unset($options['choices']);
                $newChoices = $this->getTrackingMethodsChoices();
                $newChoices[$data['method']] = $data['method'];
                $form->add(
                    'method',
                    SelectSwitchInputType::class,
                    array_replace(
                        $options,
                        [
                            'choices' => $newChoices
                        ]
                    )
                );
            }
        }
    }

    /**
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => OrderShippingTracking::class,
        ]);
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
}

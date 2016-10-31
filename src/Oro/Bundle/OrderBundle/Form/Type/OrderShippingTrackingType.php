<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
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
     * @var ShippingMethodRegistry|null
     */
    protected $shippingMethodRegistry;

    /**
     * @param ShippingMethodRegistry $shippingMethodRegistry
     */
    public function __construct($shippingMethodRegistry = null)
    {
        $this->shippingMethodRegistry = $shippingMethodRegistry;
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
        if ($this->shippingMethodRegistry !== null) {
            $methods = $this->shippingMethodRegistry->getTrackingAwareShippingMethods();

            /** @var ShippingMethodInterface $method */
            foreach ($methods as $method) {
                $this->choices[$method->getIdentifier()] = $method->getLabel();
            }
        }
        return $this->choices;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
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
     * @param FormEvent $event
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
                if (null === $options['choices'] || !array_key_exists($data->getMethod(), $options['choices'])) {
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
     * @param FormEvent $event
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
            if (null === $options['choices'] || !array_key_exists($data['method'], $options['choices'])) {
                unset($options['choices'], $options['choice_list']);
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
     * @param OptionsResolver $resolver
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

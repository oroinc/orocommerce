<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
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
     * @param array $methods
     */
    public function __construct(array $methods = null)
    {
        if (is_array($methods) && 0 < count($methods)) {
            /** @var ShippingMethodInterface $method */
            foreach ($methods as $method) {
                $this->choices[$method->getIdentifier()] = $method->getLabel();
            }
        }
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        if (null !== $this->choices) {
            $builder
                ->add(
                    'method',
                    SelectSwitchInputType::class,
                    [
                        'required' => false,
                        'choices' => $this->choices,
                        'mode' => SelectSwitchInputType::MODE_SELECT,
                        'error_bubbling' => true
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

            if (null !== $this->choices) {
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

        if (null !== $this->choices) {
            if (null === $options['choices'] || !array_key_exists($data['method'], $options['choices'])) {
                unset($options['choices'], $options['choice_list']);
                $newChoices = $this->choices;
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

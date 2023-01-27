<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;
use Oro\Bundle\ShippingBundle\Method\TrackingAwareShippingMethodsProviderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for managing order shipping tracking.
 */
class OrderShippingTrackingType extends AbstractType
{
    private TrackingAwareShippingMethodsProviderInterface $trackingAwareShippingMethodsProvider;

    public function __construct(TrackingAwareShippingMethodsProviderInterface $trackingAwareShippingMethodsProvider)
    {
        $this->trackingAwareShippingMethodsProvider = $trackingAwareShippingMethodsProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $choices = $this->getChoices();
        if ($choices) {
            $builder->add('method', SelectSwitchInputType::class, [
                'required' => false,
                'choices' => $choices,
                'mode' => SelectSwitchInputType::MODE_SELECT
            ]);
        } else {
            $builder->add('method', TextType::class, ['required' => true]);
        }
        $builder->add('number', TextType::class, ['required' => true]);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($choices) {
            $data = $event->getData();
            if ($data instanceof OrderShippingTracking) {
                $form = $event->getForm();
                $options = $form->get('method')->getConfig()->getOptions();
                if ($choices && !$this->hasChoice($options, $data->getMethod())) {
                    $form->add(
                        'method',
                        SelectSwitchInputType::class,
                        array_replace($options, ['mode' => SelectSwitchInputType::MODE_INPUT])
                    );
                }
            }
        });
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($choices) {
            $data = $event->getData();
            $form = $event->getForm();
            $options = $form->get('method')->getConfig()->getOptions();
            if ($choices && !$this->hasChoice($options, $data['method'])) {
                unset($options['choices']);
                $newChoices = $choices;
                $newChoices[$data['method']] = $data['method'];
                $form->add(
                    'method',
                    SelectSwitchInputType::class,
                    array_replace($options, ['choices' => $newChoices])
                );
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', OrderShippingTracking::class);
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_order_shipping_tracking';
    }

    private function getChoices(): array
    {
        $choices = [];
        $methods = $this->trackingAwareShippingMethodsProvider->getTrackingAwareShippingMethods();
        foreach ($methods as $method) {
            $choices[$method->getLabel()] = $method->getIdentifier();
        }

        return $choices;
    }

    private function hasChoice(array $options, string $method): bool
    {
        return null !== $options['choices'] && \in_array($method, $options['choices'], true);
    }
}

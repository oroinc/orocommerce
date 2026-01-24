<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for managing collections of shipping tracking information.
 *
 * Specialized collection form type for order shipping tracking entries with page component integration.
 * Configures the collection to use {@see OrderShippingTrackingType} entries and provides JavaScript component options
 * for enhanced UI interactions.
 */
class OrderShippingTrackingCollectionType extends AbstractType
{
    const NAME = 'oro_order_shipping_tracking_collection';

    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
    }

    /**
     * @throws AccessException
     */
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'entry_type' => OrderShippingTrackingType::class,
                'show_form_when_empty' => false,
                'page_component' => 'oroui/js/app/components/view-component',
                'page_component_options' => [
                    'view' => 'oroorder/js/app/views/shipping-tracking-collection-view',
                ],
            ]
        );
        $resolver->setAllowedTypes('page_component_options', 'array');
        $resolver->setAllowedTypes('page_component', 'string');
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['page_component'] = $options['page_component'];
        $view->vars['page_component_options'] = $options['page_component_options'];
    }
}

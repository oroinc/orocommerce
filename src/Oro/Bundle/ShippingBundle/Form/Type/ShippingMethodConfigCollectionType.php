<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\ShippingBundle\Form\EventSubscriber\MethodConfigCollectionSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for managing a collection of shipping method configurations.
 *
 * This form type provides a collection of {@see ShippingMethodConfigType} forms, allowing administrators to configure
 * multiple shipping methods within a single shipping rule.
 * It uses {@see MethodConfigCollectionSubscriber} to filter out unavailable methods.
 */
class ShippingMethodConfigCollectionType extends AbstractType
{
    const NAME = 'oro_shipping_method_config_collection';

    /**
     * @var MethodConfigCollectionSubscriber
     */
    protected $subscriber;

    public function __construct(MethodConfigCollectionSubscriber $subscriber)
    {
        $this->subscriber = $subscriber;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->subscriber);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'show_form_when_empty' => false,
            'allow_add' => true,
            'entry_type' => ShippingMethodConfigType::class,
        ]);
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['allow_add'] = false;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}

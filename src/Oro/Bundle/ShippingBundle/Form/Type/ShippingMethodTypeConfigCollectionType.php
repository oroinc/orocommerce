<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Oro\Bundle\ShippingBundle\Form\EventSubscriber\MethodTypeConfigCollectionSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for managing a collection of shipping method type configurations.
 *
 * This form type provides a collection of {@see ShippingMethodTypeConfigType} forms, allowing administrators
 * to configure multiple shipping method types (e.g., Ground, Express, Overnight)
 * within a single shipping method configuration.
 */
class ShippingMethodTypeConfigCollectionType extends AbstractType
{
    public const BLOCK_PREFIX = 'oro_shipping_method_type_config_collection';

    /** @var MethodTypeConfigCollectionSubscriber */
    protected $subscriber;

    public function __construct(MethodTypeConfigCollectionSubscriber $subscriber)
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
            'entry_type' => ShippingMethodTypeConfigType::class,
            'is_grouped' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'error_bubbling' => false,
        ]);
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['is_grouped'] = $options['is_grouped'];
    }

    /**
     * @return string
     */
    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::BLOCK_PREFIX;
    }
}

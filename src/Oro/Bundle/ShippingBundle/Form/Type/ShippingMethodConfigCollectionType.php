<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\ShippingBundle\Form\EventSubscriber\MethodConfigCollectionSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShippingMethodConfigCollectionType extends AbstractType
{
    public const NAME = 'oro_shipping_method_config_collection';

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

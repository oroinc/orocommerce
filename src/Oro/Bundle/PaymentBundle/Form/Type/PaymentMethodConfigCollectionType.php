<?php

namespace Oro\Bundle\PaymentBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\PaymentBundle\Form\EventSubscriber\RuleMethodConfigCollectionSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentMethodConfigCollectionType extends AbstractType
{
    public const NAME = 'oro_payment_method_config_collection';

    /**
     * @var RuleMethodConfigCollectionSubscriber
     */
    protected $subscriber;

    public function __construct(RuleMethodConfigCollectionSubscriber $subscriber)
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
            'entry_type' => PaymentMethodConfigType::class,
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

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}

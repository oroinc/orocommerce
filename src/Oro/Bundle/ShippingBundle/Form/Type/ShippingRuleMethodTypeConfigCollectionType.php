<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Oro\Bundle\ShippingBundle\Form\EventSubscriber\RuleMethodTypeConfigCollectionSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShippingRuleMethodTypeConfigCollectionType extends AbstractType
{
    const NAME = 'oro_shipping_rule_method_type_config_collection';

    /** @var RuleMethodTypeConfigCollectionSubscriber */
    protected $subscriber;

    /**
     * @param RuleMethodTypeConfigCollectionSubscriber $subscriber
     */
    public function __construct(RuleMethodTypeConfigCollectionSubscriber $subscriber)
    {
        $this->subscriber = $subscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->subscriber);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'type' => ShippingRuleMethodTypeConfigType::class,
            'is_grouped' => false,
            'allow_add' => true,
            'allow_delete' => true,
        ]);
    }

    /**
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['is_grouped'] = $options['is_grouped'];
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return CollectionType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}

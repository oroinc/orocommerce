<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ShippingBundle\Form\EventSubscriber\RuleConfigurationSubscriber;

class ShippingRuleConfigurationCollectionType extends AbstractType
{
    const NAME = 'orob2b_shipping_rule_config_collection';

    /** @var RuleConfigurationSubscriber */
    protected $subscriber;

    /**
     * @param RuleConfigurationSubscriber $subscriber
     */
    public function __construct(RuleConfigurationSubscriber $subscriber)
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
            'entry_type' => ShippingRuleConfigurationType::NAME,
        ]);
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

<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ShippingBundle\Form\EventSubscriber\RuleConfigurationSubscriber;

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
            'type' => ShippingRuleConfigurationType::NAME,
            'error_bubbling' => false,
            'cascade_validation' => true,
        ]);
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'collection';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}

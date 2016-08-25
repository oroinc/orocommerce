<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodConfig;
use Oro\Bundle\ShippingBundle\Form\EventSubscriber\RuleMethodConfigSubscriber;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShippingRuleMethodConfigType extends AbstractType
{
    const NAME = 'oro_shipping_rule_method_config';

    /**
     * @var ShippingMethodRegistry
     */
    protected $methodRegistry;

    /**
     * @var RuleMethodConfigSubscriber
     */
    protected $subscriber;

    /**
     * @param RuleMethodConfigSubscriber $subscriber
     * @param ShippingMethodRegistry $methodRegistry
     */
    public function __construct(RuleMethodConfigSubscriber $subscriber, ShippingMethodRegistry $methodRegistry)
    {
        $this->subscriber = $subscriber;
        $this->methodRegistry = $methodRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('method', ChoiceType::class, [
            'choices' => array_reduce(
                $this->methodRegistry->getShippingMethods(),
                function (array $result, ShippingMethodInterface $method) {
                    $result[$method->getIdentifier()] = $method->getLabel();
                    return $result;
                },
                []
            ),
        ]);
        $builder->add('typeConfigs', ShippingRuleMethodTypeConfigCollectionType::class);
        $builder->add('options', HiddenType::class, ['compound' => true]);

        $builder->addEventSubscriber($this->subscriber);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ShippingRuleMethodConfig::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}

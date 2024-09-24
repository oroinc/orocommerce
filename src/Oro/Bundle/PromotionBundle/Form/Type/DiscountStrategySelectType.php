<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DiscountStrategySelectType extends AbstractType
{
    const NAME = 'oro_discount_strategy_select';

    /**
     * @var StrategyRegistry
     */
    private $strategyRegistry;

    public function __construct(StrategyRegistry $strategyRegistry)
    {
        $this->strategyRegistry = $strategyRegistry;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = [];
        foreach ($this->strategyRegistry->getStrategies() as $alias => $strategy) {
            $choices[$strategy->getLabel()] = $alias;
        }

        $resolver->setDefaults([
            'choices' => $choices,
        ]);
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
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}

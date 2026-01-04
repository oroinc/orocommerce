<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Oro\Bundle\PricingBundle\PricingStrategy\StrategyRegister;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PriceListStrategySelectType extends AbstractType
{
    public const NAME = 'oro_pricing_list_strategy_selection';
    public const ALIAS = 'oro.pricing.system_configuration.fields.strategy_type.choices.';

    /**
     * @var StrategyRegister
     */
    protected $strategyRegister;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var array
     */
    protected $strategies = [];

    public function __construct(StrategyRegister $priceStrategyRegister, TranslatorInterface $translator)
    {
        $this->strategyRegister = $priceStrategyRegister;
        $this->translator = $translator;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => $this->getChoices(),
        ]);
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
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

    /**
     * @return array
     */
    protected function getChoices()
    {
        $choices = [];

        foreach ($this->getStrategies() as $strategy => $value) {
            $choices[$this->translator->trans(self::ALIAS.$strategy)] = $strategy;
        }

        return $choices;
    }

    /**
     * @return array
     */
    protected function getStrategies()
    {
        if (!$this->strategies) {
            $this->strategies = $this->strategyRegister->getStrategies();
        }

        return $this->strategies;
    }
}

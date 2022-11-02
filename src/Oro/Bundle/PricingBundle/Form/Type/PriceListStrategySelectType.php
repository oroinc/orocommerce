<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Oro\Bundle\PricingBundle\PricingStrategy\StrategyRegister;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PriceListStrategySelectType extends AbstractType
{
    const NAME = 'oro_pricing_list_strategy_selection';
    const ALIAS = 'oro.pricing.system_configuration.fields.strategy_type.choises.';

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

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => $this->getChoices(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
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

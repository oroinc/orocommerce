<?php

namespace Oro\Bundle\PromotionBundle\Normalizer;

use Oro\Bundle\RuleBundle\Entity\Rule;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This class convert promotion Rule entity to array and vice versa
 */
class RuleNormalizer implements NormalizerInterface
{
    const REQUIRED_OPTIONS = [
        'name',
        'sortOrder',
        'isStopProcessing',
    ];

    /**
     * @param Rule $rule
     * @return array
     */
    public function normalize($rule)
    {
        if (!$rule instanceof Rule) {
            throw new \InvalidArgumentException('Argument rule should be instance of Rule entity');
        }

        return [
            'name' => $rule->getName(),
            'expression' => $rule->getExpression(),
            'sortOrder' => $rule->getSortOrder(),
            'isStopProcessing' => $rule->isStopProcessing(),
        ];
    }

    /**
     * @param array $ruleData
     * @return Rule
     */
    public function denormalize(array $ruleData)
    {
        $resolver = $this->getOptionResolver();
        $ruleData = $resolver->resolve($ruleData);

        $rule = new Rule();
        $rule->setName($ruleData['name'])
            ->setExpression($ruleData['expression'])
            ->setSortOrder($ruleData['sortOrder'])
            ->setEnabled(true)
            ->setStopProcessing($ruleData['isStopProcessing']);

        return $rule;
    }

    /**
     * @return OptionsResolver
     */
    private function getOptionResolver()
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(self::REQUIRED_OPTIONS);

        $resolver->setDefaults([
            'expression' => null
        ]);

        $resolver->setAllowedTypes('name', ['string']);
        $resolver->setAllowedTypes('expression', ['string', 'null']);
        $resolver->setAllowedTypes('sortOrder', ['integer']);
        $resolver->setAllowedTypes('isStopProcessing', ['boolean']);

        return $resolver;
    }
}

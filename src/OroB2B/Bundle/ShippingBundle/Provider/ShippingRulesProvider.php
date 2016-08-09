<?php

namespace OroB2B\Bundle\ShippingBundle\Provider;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;
use OroB2B\Bundle\ShippingBundle\Method\ShippingMethodRegistry;

class ShippingRulesProvider
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ShippingMethodRegistry
     */
    protected $shippingMethodRegistry;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ShippingMethodRegistry $shippingMethodRegistry
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ShippingMethodRegistry $shippingMethodRegistry
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->shippingMethodRegistry = $shippingMethodRegistry;
    }

    /**
     * @return ShippingRule[]
     */
    public function getShippingRules()
    {
        return $this->getShippingRuleRepository()->findAll();
    }

    /**
     * @param ShippingContextAwareInterface|null $context
     * @return ShippingRule[]
     */
    public function getApplicableShippingRules(ShippingContextAwareInterface $context = null)
    {
        /** @var ShippingRule[] $rules */
        $rules = $this->getShippingRules();
        $shippingRuleCntx = $context->getShippingContext();
        if ($shippingRuleCntx) {
            $applicableRules = [];
            foreach ($rules as $rule) {
                if ($this->evaluateConditions($shippingRuleCntx, $rule->getConditions())) {
                    $applicableRules[$rule->getPriority()] = $rule;
                }
            }
            ksort($applicableRules);
            return $applicableRules;
        } else {
            return $rules;
        }
    }

    /**
     * @return EntityRepository
     */
    protected function getShippingRuleRepository()
    {
        return $this->doctrineHelper
            ->getEntityManagerForClass('OroB2BShippingBundle:ShippingRule')
            ->getRepository('OroB2BShippingBundle:ShippingRule')
        ;
    }

    /**
     * @param array $context
     * @param string $conditions
     * @return string
     */
    protected function evaluateConditions($context, $conditions)
    {
        $language = new ExpressionLanguage();
        try {
            $result = ($language->evaluate($conditions, $context));
        } catch (\Exception $e) {
            $result = false;
        }

        return $result;
    }
}

<?php

namespace OroB2B\Bundle\ShippingBundle\Provider;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
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
        $repo = $this->getShippingRuleRepository();
        return $repo->findAll();
    }

    /**
     * @param ShippingContextAwareInterface|null $context
     * @return ShippingRule[]
     */
    public function getApplicableShippingRules($context = null)
    {
        /** @var ShippingRule[] $rules */
        $rules = $this->getShippingRules();
        $shippingRuleCntx = $context/*->getShippingContext()*/;
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
        $repo = $this->doctrineHelper
            ->getEntityManagerForClass('OroB2BShippingBundle:ShippingRule')
            ->getRepository('OroB2BShippingBundle:ShippingRule');

        return $repo;
    }

    /**
     * @param object $context
     * @param string $conditions
     * @return string
     */
    protected function evaluateConditions($context, $conditions)
    {
        $language = new ExpressionLanguage();
        $result = ($language->evaluate(
            $conditions,
            array(
                'context' => $context,
            )
        ));
        return $result;
    }
}

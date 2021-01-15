<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\RuleBundle\Entity\RuleInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Symfony\Component\Yaml\Yaml;

class LoadShippingMethodsConfigsRules extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getShippingMethodsConfigsRulesData() as $reference => $data) {
            $this->loadShippingMethodsConfigsRule($reference, $data, $manager);
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    protected function getShippingMethodsConfigsRulesData()
    {
        return Yaml::parse(file_get_contents(__DIR__.'/data/shipping_methods_configs_rules.yml'));
    }

    /**
     * @param string        $reference
     * @param array         $data
     * @param ObjectManager $manager
     */
    private function loadShippingMethodsConfigsRule($reference, $data, ObjectManager $manager)
    {
        $rule = $this->buildRule($reference, $data['rule']);

        $configRule = $this->createMethodsConfigsRule($rule, $data['currency']);

        $manager->persist($configRule);

        $this->setReference($reference, $configRule);
    }

    /**
     * @param RuleInterface $rule
     * @param               $currency
     *
     * @return ShippingMethodsConfigsRule
     */
    private function createMethodsConfigsRule(RuleInterface $rule, $currency)
    {
        $configRule = new ShippingMethodsConfigsRule();

        return $configRule->setRule($rule)
            ->setCurrency($currency);
    }

    /**
     * @param string $reference
     * @param array  $ruleData
     *
     * @return RuleInterface
     */
    private function buildRule($reference, $ruleData)
    {
        $rule = new Rule();

        return $rule->setName($reference)
            ->setEnabled($ruleData['enabled'])
            ->setSortOrder($ruleData['sortOrder'])
            ->setExpression($ruleData['expression']);
    }
}

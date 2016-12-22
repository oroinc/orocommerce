<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\Entity\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Symfony\Component\Yaml\Yaml;

class LoadPaymentMethodsConfigsRuleData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\RuleBundle\Tests\Functional\Entity\DataFixtures\LoadRulesDataFixture'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getPaymentMethodsConfigsRulesData() as $reference => $data) {
            $entity = new PaymentMethodsConfigsRule();

            /**
             * @var Rule $rule
             */
            $rule = $this->getReference($data['rule_reference']);

            $entity
                ->setCurrency($data['currency'])
                ->setRule($rule);

            $manager->persist($entity);

            $this->setReference($reference, $entity);
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    protected function getPaymentMethodsConfigsRulesData()
    {
        return Yaml::parse(file_get_contents(__DIR__.'/data/basic_payment_methods_configs_rules.yml'));
    }
}

<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\Entity\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Symfony\Component\Yaml\Yaml;

class LoadPaymentMethodConfigData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            LoadPaymentMethodsConfigsRuleData::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getPaymentMethodsConfigsRulesData() as $reference => $data) {
            $entity = new PaymentMethodConfig();

            /** @var PaymentMethodsConfigsRule $rule */
            $rule = $this->getReference($data['configs_rule_reference']);

            $entity
                ->setType($data['type'])
                ->setMethodsConfigsRule($rule);

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
        return Yaml::parse(file_get_contents(__DIR__.'/data/basic_payment_method_configs.yml'));
    }
}

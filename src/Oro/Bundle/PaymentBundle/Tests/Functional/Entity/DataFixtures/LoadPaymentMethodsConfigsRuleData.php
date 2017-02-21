<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\Entity\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\RuleBundle\Entity\RuleInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Symfony\Component\Yaml\Yaml;

class LoadPaymentMethodsConfigsRuleData extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

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
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $user = $this->getFirstUser($manager);
        $organization = $user->getOrganization();

        foreach ($this->getPaymentMethodsConfigsRulesData() as $reference => $data) {
            $entity = new PaymentMethodsConfigsRule();

            /** @var RuleInterface $rule */
            $rule = $this->getReference($data['rule_reference']);

            $entity
                ->setCurrency($data['currency'])
                ->setRule($rule)
                ->setOrganization($organization);

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

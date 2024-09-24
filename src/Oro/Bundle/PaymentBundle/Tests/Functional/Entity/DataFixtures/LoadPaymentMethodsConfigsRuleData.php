<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\Entity\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\RuleBundle\Tests\Functional\Entity\DataFixtures\LoadRulesDataFixture;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Yaml\Yaml;

class LoadPaymentMethodsConfigsRuleData extends AbstractFixture implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [LoadRulesDataFixture::class, LoadUser::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
        $organization = $user->getOrganization();
        foreach ($this->getPaymentMethodsConfigsRulesData() as $reference => $data) {
            $entity = new PaymentMethodsConfigsRule();
            $entity->setCurrency($data['currency']);
            $entity->setRule($this->getReference($data['rule_reference']));
            $entity->setOrganization($organization);
            $manager->persist($entity);
            $this->setReference($reference, $entity);
        }
        $manager->flush();
    }

    protected function getPaymentMethodsConfigsRulesData(): array
    {
        return Yaml::parse(file_get_contents(__DIR__.'/data/basic_payment_methods_configs_rules.yml'));
    }
}

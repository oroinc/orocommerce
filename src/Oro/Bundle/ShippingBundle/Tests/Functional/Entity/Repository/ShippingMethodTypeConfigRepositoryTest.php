<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodTypeConfigRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingMethodsConfigsRulesWithConfigs;
use Oro\Bundle\ShippingBundle\Tests\Functional\Helper\FlatRateIntegrationTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ShippingMethodTypeConfigRepositoryTest extends WebTestCase
{
    use FlatRateIntegrationTrait;

    private ShippingMethodTypeConfigRepository $repository;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([
            LoadShippingMethodsConfigsRulesWithConfigs::class
        ]);

        $this->repository = self::getContainer()->get('doctrine')
            ->getRepository(ShippingMethodTypeConfig::class);
    }

    public function testFindShippingMethodTypeConfigConfigsByMethodAndType()
    {
        $ids = $this->repository->findIdsByMethodAndType(
            $this->getFlatRateIdentifier(),
            $this->getFlatRatePrimaryIdentifier()
        );

        self::assertContains($this->getFirstTypeId('shipping_rule.1'), $ids);
        self::assertContains($this->getFirstTypeId('shipping_rule.2'), $ids);
    }

    private function getFirstTypeId(string $ruleReference): int
    {
        /** @var ShippingMethodConfig $methodConfig */
        $methodConfig = $this->getReference($ruleReference)->getMethodConfigs()->first();
        return $methodConfig->getTypeConfigs()->first()->getId();
    }

    public function testDeleteMethodConfigByIds()
    {
        $ids = [
            $this->getFirstTypeId('shipping_rule.1'),
            $this->getFirstTypeId('shipping_rule.2'),
        ];

        self::assertCount(2, $this->repository->findBy(['id' => $ids]));

        $this->repository->deleteByIds($ids);

        self::assertEmpty($this->repository->findBy(['id' => $ids]));
    }

    public function testFindEnabledByMethodIdentifier()
    {
        $method = $this->getFlatRateIdentifier();

        $actual = $this->repository->findEnabledByMethodIdentifier($method);

        self::assertContains($this->getFirstType('shipping_rule.4'), $actual);
        self::assertContains($this->getFirstType('shipping_rule.9'), $actual);
        self::assertNotContains($this->getFirstType('shipping_rule_without_type_configs'), $actual);
        self::assertNotContains($this->getFirstType('shipping_rule_with_disabled_type_configs'), $actual);
    }

    private function getFirstType(string $ruleReference): ?ShippingMethodTypeConfig
    {
        /** @var ShippingMethodConfig $methodConfig */
        $methodConfig = $this->getReference($ruleReference)->getMethodConfigs()->first();
        $typeConfig = $methodConfig->getTypeConfigs()->first();

        return false !== $typeConfig ? $typeConfig : null;
    }
}

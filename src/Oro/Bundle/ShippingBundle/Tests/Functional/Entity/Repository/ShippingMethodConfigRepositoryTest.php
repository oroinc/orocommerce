<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodConfigRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingMethodsConfigsRules;
use Oro\Bundle\ShippingBundle\Tests\Functional\Helper\FlatRateIntegrationTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ShippingMethodConfigRepositoryTest extends WebTestCase
{
    use FlatRateIntegrationTrait;

    /**
     * @var ShippingMethodConfigRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->loadFixtures([
            LoadShippingMethodsConfigsRules::class
        ]);

        $this->repository = static::getContainer()->get('doctrine')
            ->getRepository('OroShippingBundle:ShippingMethodConfig');
    }

    public function testDeleteByMethod()
    {
        static::assertNotEmpty(
            $this->repository->findBy(
                [
                    'method' => $this->getFlatRateIdentifier(),
                ]
            )
        );

        $this->repository->deleteByMethod($this->getFlatRateIdentifier());

        static::assertEmpty(
            $this->repository->findBy(
                [
                    'method' => $this->getFlatRateIdentifier(),
                ]
            )
        );
    }

    public function testFindMethodConfigIdsWithoutTypeConfigs()
    {
        /** @var ShippingMethodsConfigsRule $rule */
        $rule = $this->getReference('shipping_rule_without_type_configs');

        /** @var ShippingMethodConfig $methodConfig */
        $methodConfig = $rule->getMethodConfigs()->first();

        static::assertEmpty($methodConfig->getTypeConfigs());

        $ids = $this->repository->findIdsWithoutTypeConfigs();

        static::assertEquals([$methodConfig->getId()], $ids);
    }

    public function testDeleteMethodConfigByIds()
    {
        /** @var ShippingMethodsConfigsRule $rule */
        $rule1 = $this->getReference('shipping_rule.1');
        $rule2 = $this->getReference('shipping_rule_without_type_configs');

        $ids = [
            $rule1->getMethodConfigs()->first()->getId(),
            $rule2->getMethodConfigs()->first()->getId(),
        ];

        static::assertCount(2, $this->repository->findBy(['id' => $ids]));

        $this->repository->deleteByIds($ids);

        static::assertEmpty($this->repository->findBy(['id' => $ids]));
    }
}

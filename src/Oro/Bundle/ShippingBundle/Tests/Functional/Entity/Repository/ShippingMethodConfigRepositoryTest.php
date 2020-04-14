<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodConfigRepository;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingMethodTypeConfigsWithFakeTypes;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ShippingMethodConfigRepositoryTest extends WebTestCase
{
    /**
     * @var ShippingMethodConfigRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->loadFixtures([
            LoadShippingMethodTypeConfigsWithFakeTypes::class,
        ]);

        $this->repository = static::getContainer()->get('doctrine')
            ->getRepository('OroShippingBundle:ShippingMethodConfig');
    }

    public function testDeleteByMethod()
    {
        $method = 'ups';

        static::assertNotEmpty($this->repository->findByMethod($method));

        $this->repository->deleteByMethod($method);

        static::assertEmpty($this->repository->findByMethod($method));
    }

    public function testFindMethodConfigIdsWithoutTypeConfigs()
    {
        $methodConfig = $this->getReference('shipping_rule.3.method_config_without_type_configs');

        static::assertEmpty($methodConfig->getTypeConfigs());

        $ids = $this->repository->findIdsWithoutTypeConfigs();

        static::assertEquals([$methodConfig->getId()], $ids);
    }

    public function testDeleteMethodConfigByIds()
    {
        $ids = [
            $this->getReference('shipping_rule.3.method_config_without_type_configs')->getId(),
        ];

        $this->repository->deleteByIds($ids);

        static::assertEmpty($this->repository->findBy(['id' => $ids]));
    }

    public function testFindByType()
    {
        $actualConfigs = $this->repository->findByMethod('flat_rate');

        $expectedConfig = $this->getReference('shipping_rule.2.method_config.1');

        static::assertContains($expectedConfig, $actualConfigs);
    }

    public function testFindByTypes()
    {
        $methods = [
            'ups',
            'flat_rate',
        ];

        $actualConfigs = $this->repository->findByMethod($methods);

        $expectedConfigs = [
            $this->getReference('shipping_rule.1.method_config.1'),
            $this->getReference('shipping_rule.2.method_config.1'),
        ];

        foreach ($expectedConfigs as $expectedConfig) {
            static::assertContains($expectedConfig, $actualConfigs);
        }
    }
}

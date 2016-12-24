<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodTypeConfigRepository;
use Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethodType;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingMethodsConfigsRules;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ShippingMethodTypeConfigRepositoryTest extends WebTestCase
{
    /**
     * @var ShippingMethodTypeConfigRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->loadFixtures([
            LoadShippingMethodsConfigsRules::class
        ]);

        $this->repository = static::getContainer()->get('doctrine')
            ->getRepository('OroShippingBundle:ShippingMethodTypeConfig');
    }

    public function testDeleteByMethodAndType()
    {
        $methodConfig = $this->getReference('shipping_rule.1')->getMethodConfigs()->first();

        static::assertNotEmpty(
            $this->repository->findBy(
                [
                    'methodConfig' => $methodConfig,
                    'type' => FlatRateShippingMethodType::IDENTIFIER
                ]
            )
        );

        $this->repository->deleteByMethodAndType($methodConfig, FlatRateShippingMethodType::IDENTIFIER);

        static::assertEmpty(
            $this->repository->findBy(
                [
                    'methodConfig' => $methodConfig,
                    'type' => FlatRateShippingMethodType::IDENTIFIER
                ]
            )
        );
    }
}

<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingRuleMethodTypeConfigRepository;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingRules;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ShippingRuleMethodTypeConfigRepositoryTest extends WebTestCase
{
    /**
     * @var ShippingRuleMethodTypeConfigRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->loadFixtures([
            LoadShippingRules::class
        ]);

        $this->repository = static::getContainer()->get('doctrine')
            ->getRepository('OroShippingBundle:ShippingRuleMethodTypeConfig');
    }

    public function testDeleteByMethodAndType()
    {
        $methodConfig = $this->getReference('shipping_rule.1')->getMethodConfigs()->first();

        static::assertNotEmpty(
            $this->repository->findBy(
                [
                    'methodConfig' => $methodConfig,
                    'type' => 'primary'
                ]
            )
        );

        $this->repository->deleteByMethodAndType($methodConfig, 'primary');

        static::assertEmpty(
            $this->repository->findBy(
                [
                    'methodConfig' => $methodConfig,
                    'type' => 'primary'
                ]
            )
        );
    }
}

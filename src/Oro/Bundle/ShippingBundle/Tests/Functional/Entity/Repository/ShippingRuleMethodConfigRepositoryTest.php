<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingRuleMethodConfigRepository;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingRules;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ShippingRuleMethodConfigRepositoryTest extends WebTestCase
{
    /**
     * @var ShippingRuleMethodConfigRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->loadFixtures([
            LoadShippingRules::class
        ]);

        $this->repository = static::getContainer()->get('doctrine')
            ->getRepository('OroShippingBundle:ShippingRuleMethodConfig');
    }

    public function testDeleteByMethod()
    {
        static::assertNotEmpty(
            $this->repository->findBy(
                [
                    'method' => 'flat_rate',
                ]
            )
        );

        $this->repository->deleteByMethod('flat_rate');

        static::assertEmpty(
            $this->repository->findBy(
                [
                    'method' => 'flat_rate',
                ]
            )
        );
    }
}

<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodConfigRepository;
use Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethod;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingMethodsConfigsRules;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ShippingMethodConfigRepositoryTest extends WebTestCase
{
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
                    'method' => FlatRateShippingMethod::IDENTIFIER,
                ]
            )
        );

        $this->repository->deleteByMethod(FlatRateShippingMethod::IDENTIFIER);

        static::assertEmpty(
            $this->repository->findBy(
                [
                    'method' => FlatRateShippingMethod::IDENTIFIER,
                ]
            )
        );
    }
}

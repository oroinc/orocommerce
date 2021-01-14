<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListFallbackSettings;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

abstract class AbstractFallbackRepositoryTest extends WebTestCase
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadPriceListFallbackSettings::class]);
        $this->doctrine = $this->getContainer()->get('doctrine');
    }

    /**
     * @param string[] $expectedCustomers
     * @param BufferedIdentityQueryResultIterator|array $iterator
     */
    public function checkExpectedCustomers($expectedCustomers, $iterator)
    {
        $customers = [];
        $customerRepository = $this->doctrine->getRepository('OroCustomerBundle:Customer');
        foreach ($iterator as $item) {
            $customers[] = $customerRepository->find($item['id']);
            $customerRepository->find($item['id'])->getName();
        }
        $this->assertCount(count($customers), $expectedCustomers);
        foreach ($customers as $customer) {
            $this->assertContains($customer->getName(), $expectedCustomers);
        }
    }
}

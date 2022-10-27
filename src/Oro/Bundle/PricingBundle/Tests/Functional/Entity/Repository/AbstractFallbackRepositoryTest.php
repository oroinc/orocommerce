<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListFallbackSettings;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

abstract class AbstractFallbackRepositoryTest extends WebTestCase
{
    protected ManagerRegistry $doctrine;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadPriceListFallbackSettings::class]);
        $this->doctrine = $this->getContainer()->get('doctrine');
    }

    public function checkExpectedCustomers(array $expectedCustomerNames, \Iterator $iterator): void
    {
        $customers = [];
        $customerRepository = $this->doctrine->getRepository(Customer::class);
        foreach ($iterator as $item) {
            $customers[] = $customerRepository->find($item['id']);
            $customerRepository->find($item['id'])->getName();
        }
        $this->assertCount(count($customers), $expectedCustomerNames);
        foreach ($customers as $customer) {
            $this->assertContains($customer->getName(), $expectedCustomerNames);
        }
    }
}

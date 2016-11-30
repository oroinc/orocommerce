<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListFallbackSettings;

abstract class AbstractFallbackRepositoryTest extends WebTestCase
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadPriceListFallbackSettings::class]);
        $this->doctrine = $this->getContainer()->get('doctrine');
    }

    /**
     * @param string[] $expectedAccounts
     * @param BufferedQueryResultIterator|array $iterator
     */
    public function checkExpectedAccounts($expectedAccounts, $iterator)
    {
        $accounts = [];
        $accountRepository = $this->doctrine->getRepository('OroCustomerBundle:Account');
        foreach ($iterator as $item) {
            $accounts[] = $accountRepository->find($item['id']);
            $accountRepository->find($item['id'])->getName();
        }
        $this->assertCount(count($accounts), $expectedAccounts);
        foreach ($accounts as $account) {
            $this->assertContains($account->getName(), $expectedAccounts);
        }
    }
}

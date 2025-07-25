<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Command;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RemoveUnusedCombinedPriceListsCommandTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    #[\Override]
    protected function tearDown(): void
    {
        $gc = $this->getContainer()->get('oro_pricing.builder.combined_price_list_garbage_collector');
        $gc->setGcOffsetMinutes(60);
        parent::tearDown();
    }

    public function testCommand()
    {
        $gc = $this->getContainer()->get('oro_pricing.builder.combined_price_list_garbage_collector');
        $doctrine = $this->getContainer()->get('doctrine');
        $repo = $doctrine->getRepository(CombinedPriceList::class);

        $this->assertNull($repo->findOneBy(['name' => 'test_cpl']));

        $em = $doctrine->getManagerForClass(CombinedPriceList::class);
        $combinedPriceList = new CombinedPriceList();
        $combinedPriceList->setEnabled(true);
        $combinedPriceList->setName('test_cpl');
        $em->persist($combinedPriceList);
        $em->flush();

        // Check that command execution do nothing if there are not CPLs scheduled for removal
        $this->runCommand('oro:cron:price-lists:remove-unused');
        $this->assertNotNull($repo->findOneBy(['name' => 'test_cpl']));

        // Run GC to schedule CPL for removal
        $gc->cleanCombinedPriceLists();
        $gc->setGcOffsetMinutes(0);

        // Check that command execution actually removed CPLs scheduled for removal
        $this->runCommand('oro:cron:price-lists:remove-unused');
        $this->assertNull($repo->findOneBy(['name' => 'test_cpl']));
    }
}

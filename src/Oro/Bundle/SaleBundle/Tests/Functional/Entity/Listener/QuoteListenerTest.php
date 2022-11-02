<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Entity\Listener;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class QuoteListenerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadUserData::class]);
    }

    /**
     * @covers \Oro\Bundle\SaleBundle\Entity\Listener\QuoteListener::postPersist
     */
    public function testPersistQuote()
    {
        $em = self::getContainer()->get('doctrine')->getManagerForClass(Quote::class);

        $quote = new Quote();
        $quote->setOwner($this->getReference(LoadUserData::USER1));

        $this->assertNull($quote->getQid());

        $em->persist($quote);

        $this->assertEquals(null, $quote->getQid());

        $em->flush();

        $this->assertNotNull($quote->getId());

        $em->clear();

        $quote = $em->getRepository(Quote::class)->find($quote->getId());

        $this->assertEquals($quote->getId(), $quote->getQid());
    }
}

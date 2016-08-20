<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Entity\Listener;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * @dbIsolation
 */
class QuoteListenerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            'Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData',
        ]);
    }

    /**
     * @covers \Oro\Bundle\SaleBundle\Entity\Listener\QuoteListener::postPersist
     */
    public function testPersistQuote()
    {
        /* @var $em EntityManager */
        $em = static::getContainer()->get('doctrine')->getManagerForClass('OroSaleBundle:Quote');

        $quote = new Quote();
        $quote
            ->setOwner($this->getReference(LoadUserData::USER1))
        ;

        $this->assertNull($quote->getQid());

        $em->persist($quote);

        $this->assertEquals(null, $quote->getQid());

        $em->flush();

        $this->assertNotNull($quote->getId());

        $em->clear();

        $quote = $em->getRepository('OroSaleBundle:Quote')->find($quote->getId());

        $this->assertEquals($quote->getId(), $quote->getQid());
    }
}

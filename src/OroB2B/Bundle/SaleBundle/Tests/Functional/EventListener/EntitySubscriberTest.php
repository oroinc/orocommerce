<?php

namespace OroB2B\Bundle\SaleBundle\Test\Functional\EventListener;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * @dbIsolation
 */
class EntitySubscriberTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData',
        ]);
    }

    public function testPersistQuote()
    {
        /* @var $em EntityManager */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BSaleBundle:Quote');

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

        $quote = $em->getRepository('OroB2BSaleBundle:Quote')->find($quote->getId());

        $this->assertEquals($quote->getId(), $quote->getQid());
    }
}

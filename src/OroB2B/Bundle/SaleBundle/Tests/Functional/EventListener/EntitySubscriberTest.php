<?php

namespace OroB2B\Bundle\SaleBundle\Test\Functional\EventListener;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class EntitySubscriberTest extends WebTestCase
{
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
        $em = $this->getContainer()->get('doctrine')->getManager();

        $owner = $em->getRepository('OroUserBundle:User')->findOneByUsername(LoadUserData::USER1);

        $quote = new Quote();
        $quote
            ->setOwner($owner)
        ;

        $this->assertNull($quote->getQid());

        $em->persist($quote);

        $this->assertEquals('', $quote->getQid());

        $em->flush();

        $this->assertNotNull($quote->getId());

        $em->clear();

        $quote = $em->getRepository('OroB2BSaleBundle:Quote')->find($quote->getId());

        $this->assertEquals($quote->getId(), $quote->getQid());
    }
}

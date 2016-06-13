<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\Datagrid\CheckoutSource;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\SaleBundle\Datagrid\CheckoutSource\QuoteCheckoutSourceDefiner;

/**
 * @dbIsolation
 */
class QuoteCheckoutSourceDefinerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteCheckoutsData',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function testDefinesQuoteAsSource()
    {
        /* @var $em EntityManager */
        $em = static::getContainer()->get('doctrine')->getManager();

        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $securityFacade->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue('true'));

        $translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->getMock();

        $definer = new QuoteCheckoutSourceDefiner($securityFacade, $translator);

        $checkouts = $em->createQueryBuilder()
            ->select('c.id')
            ->from('OroB2BCheckoutBundle:Checkout', 'c')
            ->getQuery()
            ->getScalarResult();

        $ids = [];

        foreach ($checkouts as $checkout) {
            $ids[] = $checkout['id'];
        }

        $result = $definer->loadSources($em, $ids);

        $this->assertTrue(count($result) > 0);

        foreach ($result as $id => $source) {
            $this->assertGreaterThan(0, $id);
            $this->assertInstanceOf(
                'OroB2B\Bundle\CheckoutBundle\Datagrid\CheckoutSource\CheckoutSourceDefinition',
                $source
            );

            $this->assertTrue($source->isLinkable());
        }
    }
}

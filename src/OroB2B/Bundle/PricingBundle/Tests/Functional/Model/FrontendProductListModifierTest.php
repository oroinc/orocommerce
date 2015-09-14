<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Model;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Model\FrontendProductListModifier;
use OroB2B\Bundle\PricingBundle\Model\PriceListTreeHandler;
use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @dbIsolation
 */
class FrontendProductListModifierTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices'
            ]
        );
    }

    /**
     * @dataProvider applyPriceListLimitationsDataProvider
     * @param string|null $currency
     * @param array $expectedProductSku
     */
    public function testApplyPriceListLimitations($currency, array $expectedProductSku)
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_2');

        $user = new AccountUser();
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($user));

        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface $tokenStorage */
        $tokenStorage = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        $tokenStorage->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token));

        /** @var \PHPUnit_Framework_MockObject_MockObject|PriceListTreeHandler $priceListTreeHandler */
        $priceListTreeHandler = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Model\PriceListTreeHandler')
            ->disableOriginalConstructor()
            ->getMock();
        $priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with($user)
            ->will($this->returnValue($priceList));

        $modifier = new FrontendProductListModifier($tokenStorage, $priceListTreeHandler);

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        $qb = $em->createQueryBuilder()
            ->select('p')
            ->from('OroB2BProductBundle:Product', 'p')
            ->orderBy('p.sku');

        $modifier->applyPriceListLimitations($qb, $currency);

        /** @var Product[] $result */
        $result = $qb->getQuery()->getResult();

        $this->assertCount(count($expectedProductSku), $result);
        $sku = array_map(function (Product $product) {
            return $product->getSku();
        }, $result);
        $this->assertEquals($expectedProductSku, $sku);
    }

    /**
     * @return array
     */
    public function applyPriceListLimitationsDataProvider()
    {
        return [
            'without currency' => [
                'currency' => null,
                'expectedProductSku' => [
                    'product.1',
                    'product.2'
                ]
            ],
            'with USD' => [
                'currency' => 'USD',
                'expectedProductSku' => [
                    'product.1',
                    'product.2'
                ]
            ],
            'with EUR' => [
                'currency' => 'EUR',
                'expectedProductSku' => [
                    'product.2'
                ]
            ],
            'with MXN' => [
                'currency' => 'MXN',
                'expectedProductSku' => []
            ]
        ];
    }
}

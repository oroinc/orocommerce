<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Autocomplete;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Model\FrontendProductListModifier;
use OroB2B\Bundle\PricingBundle\Model\PriceListTreeHandler;
use OroB2B\Bundle\PricingBundle\Autocomplete\ProductPriceListAwareSearchHandler;

/**
 * @dbIsolation
 */
class ProductPriceListAwareSearchHandlerTest extends WebTestCase
{
    const TEST_ENTITY_CLASS = 'OroB2B\Bundle\ProductBundle\Entity\Product';

    /**
     * @var array
     */
    protected $testProperties = ['sku'];

    /**
     * @var ProductPriceListAwareSearchHandler
     */
    protected $searchHandler;

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
     * @dataProvider testSearchDataProvider
     * @param $search
     * @param $expected
     */
    public function testSearch($search, $expected)
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

        $searchHandler = new ProductPriceListAwareSearchHandler(
            self::TEST_ENTITY_CLASS,
            $this->testProperties,
            $modifier
        );

        $searchHandler->initDoctrinePropertiesByManagerRegistry($this->getContainer()->get('doctrine'));
        $result = $searchHandler->search($search, 1, 10);

        if ($expected) {
            $this->assertCount(count($expected), $result['results']);
            foreach ($result['results'] as $product) {
                $this->assertContains($product['sku'], $expected);
            }
        } else {
            $this->assertEmpty($result['results']);
        }
    }

    /**
     * @return array
     */
    public function testSearchDataProvider()
    {
        return [
            [
                'search' => 'product.',
                'expected' => [
                    'product.1',
                    'product.2'
                ]
            ],
            [
                'search' => 'product.;USD',
                'expected' => [
                    'product.1',
                    'product.2'
                ]
            ],
            [
                'search' => 'product.;EUR',
                'expected' => [
                    'product.2'
                ]
            ],
            [
                'search' => 'product.;CAD',
                'expected' => []
            ],
            [
                'search' => '1',
                'expected' => [
                    'product.1'
                ]
            ],
            [
                'search' => 'product.2',
                'expected' => [
                    'product.2'
                ]
            ],
            [
                'search' => 'product.100',
                'expected' => []
            ],
        ];
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Search handler is not fully configured
     */
    public function testCheckAllDependenciesInjectedException()
    {
        /** @var FrontendProductListModifier|\PHPUnit_Framework_MockObject_MockObject $modifier */
        $modifier = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Model\FrontendProductListModifier')
            ->disableOriginalConstructor()
            ->getMock();

        $searchHandler = new ProductPriceListAwareSearchHandler(
            self::TEST_ENTITY_CLASS,
            $this->testProperties,
            $modifier
        );
        $searchHandler->search('test', 1, 10);
    }
}

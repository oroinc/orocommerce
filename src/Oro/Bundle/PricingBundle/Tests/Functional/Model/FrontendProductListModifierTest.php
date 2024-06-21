<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Model;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTreeHandler;
use Oro\Bundle\PricingBundle\Model\FrontendProductListModifier;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class FrontendProductListModifierTest extends WebTestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CombinedPriceListTreeHandler
     */
    protected $priceListTreeHandler;

    /**
     * @var FrontendProductListModifier
     */
    protected $modifier;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures(
            [
                LoadCombinedProductPrices::class
            ]
        );

        $this->setupTokenStorage();
        $this->setupPriceListTreeHandler();

        $this->modifier = new FrontendProductListModifier($this->tokenStorage, $this->priceListTreeHandler);
    }

    protected function setupTokenStorage()
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->any())
            ->method('getUser')
            ->willReturn(new CustomerUser());

        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);
    }

    protected function setupPriceListTreeHandler()
    {
        $this->priceListTreeHandler = $this->createMock(CombinedPriceListTreeHandler::class);
    }

    /**
     * @dataProvider applyPriceListLimitationsDataProvider
     * @param string|null $currency
     * @param array $expectedProductSku
     * @param PriceList|null $priceList
     */
    public function testApplyPriceListLimitations($currency, array $expectedProductSku, $priceList = null)
    {
        if ($priceList) {
            $priceList = $this->getReference($priceList);
            $this->priceListTreeHandler->expects($this->never())
                ->method('getPriceList')
                ->with($this->tokenStorage->getToken()->getUser()->getCustomer());
        } else {
            $this->priceListTreeHandler->expects($this->once())
                ->method('getPriceList')
                ->with($this->tokenStorage->getToken()->getUser()->getCustomer())
                ->willReturn($this->getReference('2f'));
        }

        $qb = $this->getManager()->createQueryBuilder()
            ->select('p')
            ->from('OroProductBundle:Product', 'p')
            ->orderBy('p.sku');

        $this->modifier->applyPriceListLimitations($qb, $currency, $priceList);

        /** @var Product[] $result */
        $result = $qb->getQuery()->getResult();

        $this->assertCount(count($expectedProductSku), $result);
        $sku = array_map(
            function (Product $product) {
                return $product->getSku();
            },
            $result
        );
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
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2
                ]
            ],
            'without currency with price list' => [
                'currency' => null,
                'expectedProductSku' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_4,
                    LoadProductData::PRODUCT_5,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_7,
                ],
                'priceList' => '1f'
            ],
            'with USD' => [
                'currency' => 'USD',
                'expectedProductSku' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2
                ]
            ],
            'with EUR' => [
                'currency' => 'EUR',
                'expectedProductSku' => [
                    LoadProductData::PRODUCT_2
                ]
            ],
            'with MXN' => [
                'currency' => 'MXN',
                'expectedProductSku' => []
            ]
        ];
    }

    /**
     * @return EntityManager|object
     */
    protected function getManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    public function testApplyPriceListLimitationsMultipleTimes()
    {
        $this->priceListTreeHandler->expects($this->exactly(3))
            ->method('getPriceList')
            ->with($this->tokenStorage->getToken()->getUser()->getCustomer())
            ->willReturn($this->getReference('2f'));

        $qb = $this->getManager()->createQueryBuilder()
            ->select('p')
            ->from('OroProductBundle:Product', 'p')
            ->orderBy('p.sku');

        $this->modifier->applyPriceListLimitations($qb);
        $this->modifier->applyPriceListLimitations($qb, 'EUR');
        $this->modifier->applyPriceListLimitations($qb, 'USD');

        /** @var Product[] $result */
        $result = $qb->getQuery()->getResult();

        $this->assertCount(1, $result);
        $this->assertEquals(LoadProductData::PRODUCT_2, $result[0]->getSku());
    }
}

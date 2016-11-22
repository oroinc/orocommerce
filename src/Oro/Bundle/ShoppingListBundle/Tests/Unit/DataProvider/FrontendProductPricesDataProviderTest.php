<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\DataProvider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\DataProvider\FrontendProductPricesDataProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

class FrontendProductPricesDataProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const TEST_CURRENCY = 'USD';

    /**
     * @var FrontendProductPricesDataProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductPriceProvider
     */
    protected $productPriceProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|UserCurrencyManager
     */
    protected $userCurrencyManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PriceListRequestHandler
     */
    protected $priceListRequestHandler;

    public function setUp()
    {
        $this->productPriceProvider = $this->getMockBuilder('Oro\Bundle\PricingBundle\Provider\ProductPriceProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->userCurrencyManager = $this->getMockBuilder('Oro\Bundle\PricingBundle\Manager\UserCurrencyManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceListRequestHandler = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Model\PriceListRequestHandler')
            ->disableOriginalConstructor()->getMock();

        $this->provider = new FrontendProductPricesDataProvider(
            $this->productPriceProvider,
            $this->securityFacade,
            $this->userCurrencyManager,
            $this->priceListRequestHandler
        );
    }

    /**
     * @dataProvider getDataDataProvider
     * @param ProductPriceCriteria $criteria
     * @param Price $price
     * @param AccountUser|null $accountUser
     * @param array $lineItems
     */
    public function testGetProductsPrices(
        ProductPriceCriteria $criteria,
        Price $price,
        AccountUser $accountUser = null,
        array $lineItems = null
    ) {
        $expected = null;
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($accountUser);

        if ($accountUser) {
            $this->userCurrencyManager->expects($this->once())
                ->method('getUserCurrency')
                ->willReturn(self::TEST_CURRENCY);

            /** @var BasePriceList $priceList */
            $priceList = $this->getEntity('Oro\Bundle\PricingBundle\Entity\BasePriceList', ['id' => 1]);
            $this->priceListRequestHandler->expects($this->once())
                ->method('getPriceListByAccount')
                ->willReturn($priceList);

            $this->productPriceProvider->expects($this->once())
                ->method('getMatchedPrices')
                ->with([$criteria], $priceList)
                ->willReturn([
                    $criteria->getIdentifier() => $price
                ]);

            $expected = ['42' => ['test' => $price]];
        }

        $result = $this->provider->getProductsMatchedPrice($lineItems);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getDataDataProvider()
    {
        /** @var Product $product */
        $product = $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id' => 42]);
        $productUnit = new ProductUnit();
        $productUnit->setCode('test');
        $quantity = 100;

        $lineItem = new LineItem();
        $lineItem->setProduct($product);
        $lineItem->setUnit($productUnit);
        $lineItem->setQuantity($quantity);

        $criteria = new ProductPriceCriteria($product, $productUnit, $quantity, self::TEST_CURRENCY);

        $price = new Price();
        $price->setValue('123');
        $price->setCurrency(self::TEST_CURRENCY);

        return [
            'with account user' => [
                'criteria' => $criteria,
                'price' => $price,
                'accountUser' => new AccountUser(),
                'lineItems' => [$lineItem]
            ],
            'without account user' => [
                'criteria' => $criteria,
                'price' => $price,
                'accountUser' => null,
                'lineItems' => [$lineItem]
            ],
        ];
    }
}

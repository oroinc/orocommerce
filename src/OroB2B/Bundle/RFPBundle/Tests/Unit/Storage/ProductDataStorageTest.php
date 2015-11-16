<?php

namespace OroB2B\Bundle\RFPBundle\Test\Unit\Storage;

use Oro\Bundle\CurrencyBundle\Model\OptionalPrice as Price;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage as Storage;
use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPBundle\Entity\RequestProductItem;
use OroB2B\Bundle\RFPBundle\Storage\ProductDataStorage;

class ProductDataStorageTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Storage */
    protected $storage;

    /** @var ProductDataStorage */
    protected $productDataStorage;

    protected function setUp()
    {
        $this->storage = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|NumberFormatter $numberFormatter */
        $numberFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NumberFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $numberFormatter->expects($this->any())
            ->method('formatCurrency')
            ->willReturnCallback(
                function ($price, $currency) {
                    return $price . $currency;
                }
            );

        /** @var \PHPUnit_Framework_MockObject_MockObject|ProductUnitValueFormatter $productUnitValueFormatter */
        $productUnitValueFormatter = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter')
            ->disableOriginalConstructor()
            ->getMock();


        $productUnitValueFormatter->expects($this->any())
            ->method('formatShort')
            ->willReturnCallback(
                function ($quantity, $productUnit) {
                    return $quantity . $productUnit;
                }
            );

        $this->productDataStorage = new ProductDataStorage(
            $this->storage,
            $numberFormatter,
            $productUnitValueFormatter
        );
    }

    protected function tearDown()
    {
        unset($this->storage, $this->productDataStorage);
    }

    public function testSaveToStorage()
    {
        $accountId = 10;
        $accountUserId = 42;
        $productSku = 'testSku';
        $comment = 'Test Comment';
        $unitCode = 'kg';
        $unitPrecision = 3;
        $requestProductItemQuantity = 10;
        $priceValue = 100;
        $priceCurrency = 'USD';

        /** @var Account $account */
        $account = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => $accountId]);

        /** @var AccountUser $accountUser */
        $accountUser = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUser', ['id' => $accountUserId]);

        $price = Price::create($priceValue, $priceCurrency);

        $productUnit = (new ProductUnit())
            ->setCode($unitCode)
            ->setDefaultPrecision($unitPrecision);

        $requestProductItem = (new RequestProductItem())
            ->setPrice($price)
            ->setQuantity($requestProductItemQuantity)
            ->setProductUnit($productUnit);

        $requestProductItem2 = (new RequestProductItem())
            ->setQuantity($requestProductItemQuantity)
            ->setProductUnit($productUnit);

        $product = (new Product())
            ->setSku($productSku);

        $requestProduct = (new RequestProduct())
            ->addRequestProductItem($requestProductItem)
            ->addRequestProductItem($requestProductItem2)
            ->setProduct($product)
            ->setComment($comment);

        $request = (new Request())
            ->setAccount($account)
            ->setAccountUser($accountUser)
            ->addRequestProduct($requestProduct);

        $this->storage->expects($this->once())
            ->method('set')
            ->with(
                [
                    'withOffers'                   => 1,
                    Storage::ENTITY_DATA_KEY       => [
                        'accountUser' => $accountUserId,
                        'account'     => $accountId
                    ],
                    Storage::ENTITY_ITEMS_DATA_KEY => [
                        [
                            Storage::PRODUCT_SKU_KEY => $productSku,
                            'comment'                => $comment,
                            'offers'                 => [
                                [
                                    'quantity'          => $requestProductItemQuantity,
                                    'unit'              => $productUnit,
                                    'currency'          => $priceCurrency,
                                    'price'             => $priceValue,
                                ],
                                [
                                    'quantity'          => $requestProductItemQuantity,
                                    'unit'              => $productUnit,
                                    'currency'          => null,
                                    'price'             => 0,
                                ]
                            ],
                        ]
                    ]
                ]
            );

        $this->productDataStorage->saveToStorage($request);
    }
}

<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Handler;

use Doctrine\DBAL\DBALException;

use Oro\Component\Testing\Unit\FormHandlerTestCase;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\CurrencyBundle\Model\OptionalPrice;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Form\Handler\QuoteCreateOrderHandler;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderProduct;
use OroB2B\Bundle\OrderBundle\Entity\OrderProductItem;

class QuoteCreateOrderHandlerTest extends FormHandlerTestCase
{
    /**
     * @var QuoteCreateOrderHandler
     */
    protected $handler;

    /**
     * @var Quote
     */
    protected $entity;

    /**
     * @var User
     */
    protected static $user;

    /**
     * @var AccountUser
     */
    protected static $accountUser;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->entity   = new Quote();
        $this->entity->setOwner($this->getAdminUser());
        $this->handler  = new QuoteCreateOrderHandler(
            $this->form,
            $this->request,
            $this->manager,
            $this->getFrontendUser()
        );
    }

    public function testProcessValidData()
    {
    }

    /**
     * @param Quote $inputData
     * @param Order $expectedData
     *
     * @dataProvider processValidDataProvider
     */
    public function testProcessValidQuote(Quote $inputData, Order $expectedData)
    {
        $this->form->expects(static::once())
            ->method('setData')
            ->with($inputData);

        $this->request->setMethod('POST');

        $this->form->expects(static::once())
            ->method('submit')
            ->with($this->request);

        $this->form->expects(static::once())
            ->method('isValid')
            ->willReturn(true);

        $this->manager->expects(static::once())
            ->method('persist')
            ->with($expectedData);

        $this->manager->expects(static::once())
            ->method('flush');

        static::assertNull($this->handler->getOrder());

        static::assertTrue($this->handler->process($inputData));

        static::assertEquals($expectedData, $this->handler->getOrder());
    }

    /**
     * @return array
     */
    public function processValidDataProvider()
    {
        $productUnit = (new ProductUnit())
            ->setCode('item1')
        ;
        $product = new Product();

        $quoteProductOffer = (new QuoteProductOffer())
            ->setQuantity(10)
            ->setPrice(OptionalPrice::create(20, 'USD'))
            ->setPriceType(QuoteProductOffer::PRICE_TYPE_UNIT)
            ->setProductUnit($productUnit)
        ;
        $quoteProduct = (new QuoteProduct())
            ->setProduct($product)
            ->setComment('comment1')
            ->addQuoteProductOffer($quoteProductOffer)
        ;
        $quote = (new Quote())
            ->setOwner($this->getAdminUser())
            ->addQuoteProduct($quoteProduct)
        ;

        $orderProductItem = (new OrderLineItem())
            ->setQuantity(10)
            ->setPrice(OptionalPrice::create(20, 'USD'))
            ->setProductUnit($productUnit)
            ->setProduct($product)
            ->setPriceType(QuoteProductOffer::PRICE_TYPE_UNIT)
            ->setFromExternalSource(true)
            ->setComment('comment1')
        ;
        $order = (new Order())
            ->setOwner($this->getAdminUser())
            ->setAccountUser($this->getFrontendUser())
            ->addLineItem($orderProductItem)
        ;
        $newQuote = (new Quote())->setOwner($this->getAdminUser());

        return [
            'empty quote' => [
                'input'     => $newQuote,
                'expected'  => (new Order)
                    ->setOwner($this->getAdminUser())
                    ->setAccountUser($this->getFrontendUser()),
            ],
            'filled quote' => [
                'input'     => $quote,
                'expected'  => $order,
            ],
        ];
    }

    public function testProcessInvalid()
    {
        $data = new Quote();
        $data->setOwner($this->getAdminUser());

        $this->form->expects($this->once())->method('setData')->with($data);
        $this->request->setMethod('POST');
        $this->form->expects($this->once())->method('submit')->with($this->request);
        $this->form->expects($this->once())->method('isValid')->willReturn(true);
        $this->manager->expects($this->once())->method('persist');

        $exception = new DBALException();

        $this->manager->expects($this->once())
            ->method('flush')
            ->will($this->throwException($exception));

        $this->assertFalse($this->handler->process($data));
        $this->assertSame($exception, $this->handler->getException());
    }

    /**
     * @return User
     */
    protected function getAdminUser()
    {
        if (!self::$user) {
            self::$user = new User();
        }

        return self::$user;
    }

    /**
     * @return AccountUser
     */
    protected function getFrontendUser()
    {
        if (!self::$accountUser) {
            self::$accountUser = new AccountUser();
        }

        return self::$accountUser;
    }
}

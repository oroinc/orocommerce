<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Handler;

use Oro\Component\Testing\Unit\FormHandlerTestCase;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\CurrencyBundle\Model\OptionalPrice;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPBundle\Entity\RequestProductItem;
use OroB2B\Bundle\RFPBundle\Form\Handler\RequestCreateQuoteHandler;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductRequest;

class RequestCreateQuoteHandlerTest extends FormHandlerTestCase
{
    /**
     * @var RequestCreateQuoteHandler
     */
    protected $handler;

    /**
     * @var Request
     */
    protected $entity;

    /**
     * @var User
     */
    protected static $user;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->entity   = new Request();
        $this->handler  = new RequestCreateQuoteHandler($this->form, $this->request, $this->manager, $this->getUser());
    }

    /**
     * @param Request $inputData
     * @param Quote $expectedData
     *
     * @dataProvider processValidDataProvider
     */
    public function testProcessValidData(Request $inputData, Quote $expectedData)
    {
        $this->form->expects($this->once())
            ->method('setData')
            ->with($inputData);

        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->manager->expects($this->once())
            ->method('persist')
            ->with($expectedData);

        $this->manager->expects($this->once())
            ->method('flush');

        $this->assertNull($this->handler->getQuote());

        $this->assertTrue($this->handler->process($inputData));

        $this->assertEquals($expectedData, $this->handler->getQuote());
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

        $requestProductItem = (new RequestProductItem())
            ->setQuantity(10)
            ->setPrice(OptionalPrice::create(20, 'USD'))
            ->setProductUnit($productUnit)
        ;
        $requestProduct = (new RequestProduct())
            ->setProduct($product)
            ->setComment('comment1')
            ->addRequestProductItem($requestProductItem)
        ;
        $request = (new Request())
            ->addRequestProduct($requestProduct)
        ;

        $quoteProductRequest = (new QuoteProductRequest())
            ->setQuantity(10)
            ->setPrice(OptionalPrice::create(20, 'USD'))
            ->setProductUnit($productUnit)
            ->setRequestProductItem($requestProductItem)
        ;
        $quoteProduct = (new QuoteProduct())
            ->setProduct($product)
            ->setCommentCustomer('comment1')
            ->setType(QuoteProduct::TYPE_REQUESTED)
            ->addQuoteProductRequest($quoteProductRequest)
        ;
        $quote = (new Quote())
            ->setRequest($request)
            ->setOwner($this->getUser())
            ->addQuoteProduct($quoteProduct)
        ;

        return [
            'empty request' => [
                'input'     => new Request(),
                'expected'  => (new Quote)
                    ->setRequest(new Request)
                    ->setOwner($this->getUser()),
            ],
            'filled request' => [
                'input'     => $request,
                'expected'  => $quote,
            ],
        ];
    }

    /**
     * @return User
     */
    protected function getUser()
    {
        if (!self::$user) {
            self::$user = new User();
        }

        return self::$user;
    }
}

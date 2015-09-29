<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Handler;

use Doctrine\DBAL\DBALException;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\Unit\FormHandlerTestCase;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\CurrencyBundle\Model\OptionalPrice;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPBundle\Entity\RequestProductItem;
use OroB2B\Bundle\RFPBundle\Form\Handler\RequestCreateQuoteHandler;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
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

        $this->entity = new Request();
        $this->handler = new RequestCreateQuoteHandler($this->form, $this->request, $this->manager, $this->getUser());
    }

    /**
     * {@inheritdoc}
     */
    public static function tearDownAfterClass()
    {
        self::$user = null;
    }

    public function testProcessValidData()
    {
    }

    /**
     * @param Request $inputData
     * @param Quote $expectedData
     *
     * @dataProvider processValidDataProvider
     */
    public function testProcessValidQuote(Request $inputData, Quote $expectedData)
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
            ->setCode('item1');
        $product = new Product();
        $accountUser = new AccountUser();
        $account = new Account();

        $requestProductItem = (new RequestProductItem())
            ->setQuantity(10)
            ->setPrice(OptionalPrice::create(20, 'USD'))
            ->setProductUnit($productUnit);
        $requestProduct = (new RequestProduct())
            ->setProduct($product)
            ->setComment('comment1')
            ->addRequestProductItem($requestProductItem);
        $request = (new Request())
            ->setAccount($account)
            ->setAccountUser($accountUser)
            ->addRequestProduct($requestProduct);

        $quoteProductRequest = (new QuoteProductRequest())
            ->setQuantity(10)
            ->setPrice(OptionalPrice::create(20, 'USD'))
            ->setProductUnit($productUnit)
            ->setRequestProductItem($requestProductItem);
        $quoteProductOffer = (new QuoteProductOffer())
            ->setQuantity(10)
            ->setProductUnit($productUnit)
            ->setPriceType(QuoteProductOffer::PRICE_TYPE_UNIT)
            ->setAllowIncrements(true);
        $quoteProduct = (new QuoteProduct())
            ->setProduct($product)
            ->setCommentAccount('comment1')
            ->setType(QuoteProduct::TYPE_REQUESTED)
            ->addQuoteProductRequest($quoteProductRequest)
            ->addQuoteProductOffer($quoteProductOffer);
        $quote = (new Quote())
            ->setAccount($account)
            ->setAccountUser($accountUser)
            ->setRequest($request)
            ->setOwner($this->getUser())
            ->addQuoteProduct($quoteProduct)
            ->setOrganization($this->getUser()->getOrganization());

        return [
            'empty request' => [
                'input' => new Request(),
                'expected' => (new Quote)
                    ->setRequest(new Request)
                    ->setOwner($this->getUser())
                    ->setOrganization($this->getUser()->getOrganization())
            ],
            'filled request' => [
                'input' => $request,
                'expected' => $quote,
            ],
        ];
    }

    public function testProcessInvalid()
    {
        $data = new Request();

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
    protected function getUser()
    {
        if (!self::$user) {
            self::$user = new User();
            self::$user->setOrganization(new Organization());
        }

        return self::$user;
    }
}

<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Form\Handler;

use Doctrine\DBAL\DBALException;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\FormHandlerTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPBundle\Entity\RequestProductItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Form\Handler\ShoppingListCreateRfpHandler;

class ShoppingListCreateRfpHandlerTest extends FormHandlerTestCase
{
    /**
     * @var ShoppingListCreateRfpHandler
     */
    protected $handler;

    /**
     * @var ShoppingList
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

        $this->entity   = new ShoppingList();
        $this->handler  = new ShoppingListCreateRfpHandler(
            $this->form,
            $this->request,
            $this->manager,
            $this->getUser()
        );
    }

    public function testProcessValidData()
    {
    }

    /**
     * @param ShoppingList $inputData
     * @param Request $expectedData
     *
     * @dataProvider processValidDataProvider
     */
    public function testProcessValidShoppingList(ShoppingList $inputData, Request $expectedData)
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
            ->method('flush');

        static::assertNull($this->handler->getRfpRequest());

        static::assertTrue($this->handler->process($inputData));

        $rfpRequest = $this->handler->getRfpRequest();
        if ($rfpRequest) {
            $rfpRequest
                ->setCreatedAt($expectedData->getCreatedAt())
                ->setUpdatedAt($expectedData->getUpdatedAt())
            ;
        }

        static::assertEquals($expectedData, $rfpRequest);
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

        $lineItem = (new LineItem())
            ->setQuantity(10)
            ->setUnit($productUnit)
            ->setProduct($product)
        ;
        $shoppingList = (new ShoppingList())
            ->addLineItem($lineItem)
        ;

        $requestProductItem = (new RequestProductItem())
            ->setQuantity(10)
            ->setProductUnit($productUnit)
        ;
        $requestProduct = (new RequestProduct())
            ->setProduct($product)
            ->addRequestProductItem($requestProductItem)
        ;
        $request = (new Request())
            ->setAccountUser($this->getUser())
            ->addRequestProduct($requestProduct)
        ;

        $emptyRequest = (new Request())
            ->setAccountUser($this->getUser())
        ;

        return [
            'empty shopping list' => [
                'input'     => new ShoppingList(),
                'expected'  => $emptyRequest,
            ],
            'filled request' => [
                'input'     => $shoppingList,
                'expected'  => $request,
            ],
        ];
    }

    public function testProcessInvalid()
    {
        $data = new ShoppingList();

        $this->form->expects(static::once())->method('setData')->with($data);
        $this->request->setMethod('POST');
        $this->form->expects(static::once())->method('submit')->with($this->request);
        $this->form->expects(static::once())->method('isValid')->willReturn(true);
        $this->manager->expects(static::once())->method('persist');

        $exception = new DBALException();

        $this->manager->expects(static::once())
            ->method('flush')
            ->will(static::throwException($exception));

        static::assertFalse($this->handler->process($data));
        static::assertSame($exception, $this->handler->getException());
    }

    /**
     * @return User
     */
    protected function getUser()
    {
        if (!self::$user) {
            self::$user = new AccountUser();
        }

        return self::$user;
    }
}

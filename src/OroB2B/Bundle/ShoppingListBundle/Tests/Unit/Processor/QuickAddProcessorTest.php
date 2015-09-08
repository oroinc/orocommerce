<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Processor;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use OroB2B\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;
use OroB2B\Bundle\ShoppingListBundle\Processor\QuickAddProcessor;

class QuickAddProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ShoppingListLineItemHandler */
    protected $handler;

    /** @var QuickAddProcessor */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProductRepository */
    protected $productRepository;

    protected function setUp()
    {
        $this->handler = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepository = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();

        $this->registry->expects($this->any())->method('getManagerForClass')->willReturn($em);
        $em->expects($this->any())->method('getRepository')->willReturn($this->productRepository);

        $this->processor = new QuickAddProcessor($this->handler, $this->registry);
        $this->processor->setProductClass('OroB2B\Bundle\ProductBundle\Entity\Product');
    }

    protected function tearDown()
    {
        unset($this->handler, $this->processor);
    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->processor->getName());
        $this->assertEquals(QuickAddProcessor::NAME, $this->processor->getName());
    }

    /**
     * @param array $data
     * @param Request $request
     * @param array $productIds
     * @param array $productQuantities
     * @dataProvider processDataProvider
     */
    public function testProcess(array $data, Request $request, array $productIds = [], array $productQuantities = [])
    {
        $this->handler->expects($this->any())->method('getShoppingList')->will(
            $this->returnCallback(
                function ($shoppingListId) {
                    if (!$shoppingListId) {
                        return $this->getEntity('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList');
                    }

                    return $this->getEntity('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', $shoppingListId);
                }
            )
        );

        $this->productRepository->expects($this->any())->method('getProductsIdsBySku')->willReturn($productIds);

        $this->handler->expects($data ? $this->once() : $this->never())->method('createForShoppingList')->with(
            $this->isInstanceOf('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList'),
            $productIds,
            $productQuantities
        );

        $this->processor->process($data, $request);
    }

    /** @return array */
    public function processDataProvider()
    {
        return [
            'empty' => [[], new Request()],
            'new shopping list' => [
                [
                    ['productSku' => 'sku1', 'productQuantity' => 2],
                    ['productSku' => 'sku2', 'productQuantity' => 3],
                ],
                new Request(),
                ['sku1' => 1, 'sku2' => 2],
                [1 => 2, 2 => 3],
            ],
            'existing shopping list' => [
                [
                    ['productSku' => 'sku1', 'productQuantity' => 2],
                    ['productSku' => 'sku2', 'productQuantity' => 3],
                ],
                new Request(['additional' => 1]),
                ['sku1' => 1, 'sku2' => 2],
                [1 => 2, 2 => 3],
            ],
            'ids sorting' => [
                [
                    ['productSku' => 'sku2', 'productQuantity' => 3],
                    ['productSku' => 'sku1', 'productQuantity' => 2],
                ],
                new Request(['additional' => 1]),
                ['sku2' => 2, 'sku1' => 1],
                [1 => 2, 2 => 3],
            ],
        ];
    }

    /**
     * @param string $className
     * @param int $id
     * @return object
     */
    protected function getEntity($className, $id = null)
    {
        $entity = new $className;

        if ($id) {
            $reflectionClass = new \ReflectionClass($className);
            $method = $reflectionClass->getProperty('id');
            $method->setAccessible(true);
            $method->setValue($entity, $id);
        }

        return $entity;
    }

    public function testIsValidationRequired()
    {
        $this->assertInternalType('bool', $this->processor->isValidationRequired());
        $this->assertTrue($this->processor->isValidationRequired());
    }

    public function testIsAllowed()
    {
        $this->handler->expects($this->once())->method('isAllowed')->willReturn(true);

        $result = $this->processor->isAllowed();
        $this->assertInternalType('bool', $result);
        $this->assertTrue($result);
    }
}

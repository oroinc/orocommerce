<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Processor;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ShoppingListBundle\Generator\MessageGenerator;
use Oro\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;
use Oro\Bundle\ShoppingListBundle\Processor\QuickAddProcessor;

abstract class AbstractQuickAddProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ShoppingListLineItemHandler */
    protected $handler;

    /** @var QuickAddProcessor */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject|MessageGenerator */
    protected $messageGenerator;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProductRepository */
    protected $productRepository;

    abstract public function getProcessorName();

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->handler = $this->getMockBuilder('Oro\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepository = $this
            ->getMockBuilder('Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->messageGenerator = $this->getMockBuilder('Oro\Bundle\ShoppingListBundle\Generator\MessageGenerator')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();

        $this->registry->expects($this->any())->method('getManagerForClass')->willReturn($em);
        $em->expects($this->any())->method('getRepository')->willReturn($this->productRepository);

        $this->processor = new QuickAddProcessor($this->handler, $this->registry, $this->messageGenerator);
        $this->processor->setProductClass('Oro\Bundle\ProductBundle\Entity\Product');
    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->processor->getName());
        $this->assertEquals($this->getProcessorName(), $this->processor->getName());
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->handler, $this->processor, $this->registry, $this->messageGenerator);
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

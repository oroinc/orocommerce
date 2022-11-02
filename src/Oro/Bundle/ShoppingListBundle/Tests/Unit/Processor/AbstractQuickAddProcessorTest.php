<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Processor;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Generator\MessageGenerator;
use Oro\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;
use Oro\Bundle\ShoppingListBundle\Processor\QuickAddProcessor;

abstract class AbstractQuickAddProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ShoppingListLineItemHandler */
    protected $handler;

    /** @var QuickAddProcessor */
    protected $processor;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    protected $registry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MessageGenerator */
    protected $messageGenerator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ProductRepository */
    protected $productRepository;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $aclHelper;

    protected function setUp(): void
    {
        $this->handler = $this->createMock(ShoppingListLineItemHandler::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->messageGenerator = $this->createMock(MessageGenerator::class);
        $this->em = $this->createMock(EntityManager::class);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->em);
        $this->em->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->productRepository);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->processor = new QuickAddProcessor(
            $this->handler,
            $this->registry,
            $this->messageGenerator,
            $this->aclHelper
        );
        $this->processor->setProductClass(Product::class);
    }

    abstract public function getProcessorName(): string;

    public function testGetName()
    {
        $this->assertIsString($this->processor->getName());
        $this->assertEquals($this->getProcessorName(), $this->processor->getName());
    }

    public function testIsValidationRequired()
    {
        $this->assertIsBool($this->processor->isValidationRequired());
        $this->assertTrue($this->processor->isValidationRequired());
    }

    public function testIsAllowed()
    {
        $this->handler->expects($this->once())
            ->method('isAllowed')
            ->willReturn(true);

        $result = $this->processor->isAllowed();
        $this->assertIsBool($result);
        $this->assertTrue($result);
    }
}

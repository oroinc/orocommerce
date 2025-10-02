<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ComponentProcessor;

use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorInterface;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class ComponentProcessorRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ComponentProcessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $processor1;

    /** @var ComponentProcessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $processor2;

    /** @var ComponentProcessorRegistry */
    private $registry;

    #[\Override]
    protected function setUp(): void
    {
        $this->processor1 = $this->createMock(ComponentProcessorInterface::class);
        $this->processor2 = $this->createMock(ComponentProcessorInterface::class);

        $container = TestContainerBuilder::create()
            ->add('processor1', $this->processor1)
            ->add('processor2', $this->processor2)
            ->getContainer($this);

        $this->registry = new ComponentProcessorRegistry(['processor1', 'processor2'], $container);
    }

    public function testGetProcessor(): void
    {
        self::assertSame($this->processor1, $this->registry->getProcessor('processor1'));
        self::assertSame($this->processor2, $this->registry->getProcessor('processor2'));
    }

    public function testGetProcessorForUnknownProcessor(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot find a processor with the name "processor3".');

        $this->registry->getProcessor('processor3');
    }

    public function testHasProcessor(): void
    {
        self::assertTrue($this->registry->hasProcessor('processor1'));
        self::assertTrue($this->registry->hasProcessor('processor2'));
        self::assertFalse($this->registry->hasProcessor('processor3'));
    }

    public function testHasAllowedProcessorsWhenAllowedProcessorsExist(): void
    {
        $this->processor1->expects(self::once())
            ->method('isAllowed')
            ->willReturn(true);
        $this->processor2->expects(self::never())
            ->method('isAllowed');

        self::assertTrue($this->registry->hasAllowedProcessors());
    }

    public function testHasAllowedProcessorsWhenNoAllowedProcessors(): void
    {
        $this->processor1->expects(self::once())
            ->method('isAllowed')
            ->willReturn(false);
        $this->processor2->expects(self::once())
            ->method('isAllowed')
            ->willReturn(false);

        self::assertFalse($this->registry->hasAllowedProcessors());
    }

    public function testGetAllowedProcessorsNames(): void
    {
        $this->processor1->expects(self::once())
            ->method('isAllowed')
            ->willReturn(true);

        $this->processor2->expects(self::once())
            ->method('isAllowed')
            ->willReturn(false);

        self::assertEquals(
            ['processor1'],
            $this->registry->getAllowedProcessorsNames()
        );
    }
}

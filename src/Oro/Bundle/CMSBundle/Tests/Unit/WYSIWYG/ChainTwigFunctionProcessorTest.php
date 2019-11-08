<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\WYSIWYG;

use Oro\Bundle\CMSBundle\WYSIWYG\ChainTwigFunctionProcessor;
use Oro\Bundle\CMSBundle\WYSIWYG\WYSIWYGProcessedDTO;
use Oro\Bundle\CMSBundle\WYSIWYG\WYSIWYGProcessedEntityDTO;
use Oro\Bundle\CMSBundle\WYSIWYG\WYSIWYGTwigFunctionProcessorInterface;

class ChainTwigFunctionProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var WYSIWYGTwigFunctionProcessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerProcessorA;

    /** @var WYSIWYGTwigFunctionProcessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerProcessorB;

    /** @var ChainTwigFunctionProcessor */
    private $processor;

    protected function setUp()
    {
        $this->innerProcessorA = $this->createMock(WYSIWYGTwigFunctionProcessorInterface::class);
        $this->innerProcessorB = $this->createMock(WYSIWYGTwigFunctionProcessorInterface::class);

        $this->processor = new ChainTwigFunctionProcessor(
            new \ArrayIterator([$this->innerProcessorA, $this->innerProcessorB])
        );
    }

    public function testGetApplicableFieldTypes(): void
    {
        $this->innerProcessorA->expects($this->once())
            ->method('getApplicableFieldTypes')
            ->willReturn(['type_A', 'type_B']);

        $this->innerProcessorB->expects($this->once())
            ->method('getApplicableFieldTypes')
            ->willReturn(['type_B', 'type_C']);

        $this->assertEquals(
            ['type_A', 'type_B', 'type_C'],
            $this->processor->getApplicableFieldTypes()
        );

        // Second call must not call inner processors
        $this->assertEquals(
            ['type_A', 'type_B', 'type_C'],
            $this->processor->getApplicableFieldTypes()
        );
    }

    public function testGetAcceptedTwigFunctions(): void
    {
        $this->innerProcessorA->expects($this->once())
            ->method('getAcceptedTwigFunctions')
            ->willReturn(['function_A', 'function_B']);

        $this->innerProcessorB->expects($this->once())
            ->method('getAcceptedTwigFunctions')
            ->willReturn(['function_B', 'function_C']);

        $this->assertEquals(
            ['function_A', 'function_B', 'function_C'],
            $this->processor->getAcceptedTwigFunctions()
        );

        // Second call must not call inner processors
        $this->assertEquals(
            ['function_A', 'function_B', 'function_C'],
            $this->processor->getAcceptedTwigFunctions()
        );
    }

    public function testProcessTwigFunctions(): void
    {
        $this->innerProcessorA->expects($this->once())
            ->method('getApplicableFieldTypes')
            ->willReturn(['type_A', 'type_B']);

        $this->innerProcessorA->expects($this->never())
            ->method('getAcceptedTwigFunctions');

        $this->innerProcessorB->expects($this->once())
            ->method('getApplicableFieldTypes')
            ->willReturn(['type_B', 'type_C']);

        $this->innerProcessorB->expects($this->once())
            ->method('getAcceptedTwigFunctions')
            ->willReturn(['function_B', 'function_C']);

        /** @var WYSIWYGProcessedEntityDTO|\PHPUnit\Framework\MockObject\MockObject $entityDto */
        $entityDto = $this->createMock(WYSIWYGProcessedEntityDTO::class);
        $entityDto->expects($this->once())
            ->method('getFieldType')
            ->willReturn('type_C');

        $processedDTO = new WYSIWYGProcessedDTO($entityDto);

        $this->innerProcessorB->expects($this->once())
            ->method('processTwigFunctions')
            ->with($processedDTO, [
                'function_C' => [['test_arg_C']],
            ])
            ->willReturn(true);

        $this->assertTrue($this->processor->processTwigFunctions(
            $processedDTO,
            [
                'function_A' => [['test_arg_A']],
                'function_C' => [['test_arg_C']],
            ]
        ));
    }

    public function testOnPreRemove()
    {
        /** @var WYSIWYGProcessedDTOTest|\PHPUnit\Framework\MockObject\MockObject $processedDTO */
        $processedDTO = $this->createMock(WYSIWYGProcessedDTO::class);

        $this->innerProcessorA->expects($this->once())
            ->method('onPreRemove')
            ->with($processedDTO)
            ->willReturn(true);

        $this->innerProcessorB->expects($this->once())
            ->method('onPreRemove')
            ->with($processedDTO)
            ->willReturn(true);

        $this->assertTrue($this->processor->onPreRemove($processedDTO));
    }
}

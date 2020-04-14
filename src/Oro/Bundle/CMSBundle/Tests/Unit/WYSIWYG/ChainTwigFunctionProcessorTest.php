<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\WYSIWYG;

use Oro\Bundle\CMSBundle\WYSIWYG\ChainTwigFunctionProcessor;
use Oro\Bundle\CMSBundle\WYSIWYG\WYSIWYGProcessedDTO;
use Oro\Bundle\CMSBundle\WYSIWYG\WYSIWYGTwigFunctionProcessorInterface;

class ChainTwigFunctionProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var WYSIWYGTwigFunctionProcessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerProcessorA;

    /** @var WYSIWYGTwigFunctionProcessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerProcessorB;

    /** @var ChainTwigFunctionProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->innerProcessorA = $this->createMock(WYSIWYGTwigFunctionProcessorInterface::class);
        $this->innerProcessorB = $this->createMock(WYSIWYGTwigFunctionProcessorInterface::class);

        $this->processor = new ChainTwigFunctionProcessor(
            new \ArrayIterator([$this->innerProcessorA, $this->innerProcessorB])
        );
    }

    public function testGetApplicableMapping(): void
    {
        $this->innerProcessorA->expects($this->once())
            ->method('getApplicableMapping')
            ->willReturn(['type_A' => ['function_1_a', 'function_2_a'], 'type_B' => ['function_1_b', 'function_2_b']]);

        $this->innerProcessorB->expects($this->once())
            ->method('getApplicableMapping')
            ->willReturn(['type_B' => ['function_2_b', 'function_3_b'], 'type_C' => ['function_1_c', 'function_2_c']]);

        $this->assertEquals(
            [
                'type_A' => ['function_1_a', 'function_2_a'],
                'type_B' => ['function_1_b', 'function_2_b', 'function_3_b'],
                'type_C' => ['function_1_c', 'function_2_c'],
            ],
            $this->processor->getApplicableMapping()
        );

        // Second call must not call inner processors
        $this->assertEquals(
            [
                'type_A' => ['function_1_a', 'function_2_a'],
                'type_B' => ['function_1_b', 'function_2_b', 'function_3_b'],
                'type_C' => ['function_1_c', 'function_2_c'],
            ],
            $this->processor->getApplicableMapping()
        );
    }

    public function testProcessTwigFunctions(): void
    {
        /** @var WYSIWYGProcessedDTO|\PHPUnit\Framework\MockObject\MockObject $processedDTO */
        $processedDTO = $this->createMock(WYSIWYGProcessedDTO::class);

        $this->innerProcessorA->expects($this->once())
            ->method('getApplicableMapping')
            ->willReturn(['type_A' => ['function_1_a', 'function_2_a'], 'type_B' => ['function_1_b', 'function_2_b']]);

        $this->innerProcessorA->expects($this->once())
            ->method('processTwigFunctions')
            ->with($processedDTO, [
                'type_A' => [
                    'function_1_a' => [['test_arg_1_a_1'], ['test_arg_1_a_2']],
                    'function_2_a' => [['test_arg_2_a_1'], ['test_arg_2_a_2']],
                ],
                'type_B' => [
                    'function_1_b' => [['test_arg_1_b_1'], ['test_arg_1_b_2']],
                    'function_2_b' => [['test_arg_1_b_1'], ['test_arg_1_b_2']],
                ],
            ])
            ->willReturn(true);

        $this->innerProcessorB->expects($this->once())
            ->method('getApplicableMapping')
            ->willReturn(['type_B' => ['function_2_b', 'function_3_b'], 'type_C' => ['function_1_c', 'function_2_c']]);

        $this->innerProcessorB->expects($this->once())
            ->method('processTwigFunctions')
            ->with($processedDTO, [
                'type_B' => [
                    'function_2_b' => [['test_arg_1_b_1'], ['test_arg_1_b_2']],
                ],
                'type_C' => [
                    'function_2_c' => [['test_arg_2_c_1'], ['test_arg_2_c_2']],
                ],
            ])
            ->willReturn(true);

        $this->assertTrue($this->processor->processTwigFunctions(
            $processedDTO,
            [
                'type_A' => [
                    'function_1_a' => [['test_arg_1_a_1'], ['test_arg_1_a_2']],
                    'function_2_a' => [['test_arg_2_a_1'], ['test_arg_2_a_2']],
                ],
                'type_B' => [
                    'function_1_b' => [['test_arg_1_b_1'], ['test_arg_1_b_2']],
                    'function_2_b' => [['test_arg_1_b_1'], ['test_arg_1_b_2']],
                ],
                'type_C' => [
                    'function_2_c' => [['test_arg_2_c_1'], ['test_arg_2_c_2']],
                ]
            ]
        ));
    }

    public function testOnPreRemove()
    {
        /** @var WYSIWYGProcessedDTO|\PHPUnit\Framework\MockObject\MockObject $processedDTO */
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

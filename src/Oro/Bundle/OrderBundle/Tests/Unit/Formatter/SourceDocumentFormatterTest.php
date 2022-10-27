<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Formatter;

use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;
use Oro\Bundle\OrderBundle\Formatter\SourceDocumentFormatter;

class SourceDocumentFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityClassNameProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $entityClassNameProvider;

    /** @var SourceDocumentFormatter */
    private $sourceDocumentFormatter;

    protected function setUp(): void
    {
        $this->entityClassNameProvider = $this->createMock(EntityClassNameProviderInterface::class);

        $this->sourceDocumentFormatter = new SourceDocumentFormatter($this->entityClassNameProvider);
    }

    /**
     * @dataProvider getProvider
     *
     * @param string $sourceDocumentClass
     * @param integer $sourceDocumentId
     * @param string $sourceDocumentIdentifier
     * @param $expectedFormat
     * @param $expectUseGetEntityClassName
     */
    public function testFormat(
        $sourceDocumentClass,
        $sourceDocumentId,
        $sourceDocumentIdentifier,
        $expectedFormat,
        $expectUseGetEntityClassName
    ) {
        if ($expectUseGetEntityClassName) {
            $this->entityClassNameProvider->expects($this->once())
                ->method('getEntityClassName')
                ->willReturn($sourceDocumentClass);
        } else {
            $this->entityClassNameProvider->expects($this->never())
                ->method('getEntityClassName');
        }

        $response = $this->sourceDocumentFormatter->format(
            $sourceDocumentClass,
            $sourceDocumentId,
            $sourceDocumentIdentifier
        );

        self::assertEquals($expectedFormat, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function getProvider()
    {
        return [
            'empty class and empty identifier' => [
                'sourceDocumentClass' => null,
                'sourceDocumentId' => null,
                'sourceDocumentIdentifier' => null,
                'expectedFormat' => '',
                'expectUseGetEntityClassName' => false
            ],
            'order without identifier' => [
                'sourceDocumentClass' => 'Order',
                'sourceDocumentId' => 1,
                'sourceDocumentIdentifier' => null,
                'expectedFormat' => 'Order 1',
                'expectUseGetEntityClassName' => true
            ],
            'order with identifier' => [
                'sourceDocumentClass' => 'Order',
                'sourceDocumentId' => 1,
                'sourceDocumentIdentifier' => 'FR1012401',
                'expectedFormat' => 'Order "FR1012401"',
                'expectUseGetEntityClassName' => true
            ],
            'order without identifier and id' => [
                'sourceDocumentClass' => 'Order',
                'sourceDocumentId' => null,
                'sourceDocumentIdentifier' => null,
                'expectedFormat' => 'Order',
                'expectUseGetEntityClassName' => true
            ]
        ];
    }
}

<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\RFPBundle\Api\Processor\SortIncludedDataProcessor;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;

class SortIncludedDataProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var SortIncludedDataProcessor */
    protected $processor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        /** @var ValueNormalizer|\PHPUnit_Framework_MockObject_MockObject $valueNormalizer */
        $valueNormalizer = $this->createMock(ValueNormalizer::class);
        $valueNormalizer->expects($this->any())
            ->method('normalizeValue')->willReturnArgument(0);

        $this->processor = new SortIncludedDataProcessor($valueNormalizer);
    }

    /**
     * @dataProvider includedDataProvider
     *
     * @param array $includedData
     * @param array $expected
     */
    public function testProcess(array $includedData, array $expected)
    {
        $context = $this->createContext($includedData);

        $this->processor->process($context);
        $requestData = $context->getRequestData();

        $this->assertEquals(
            $expected,
            array_map(
                function ($data) {
                    return $data[JsonApiDoc::TYPE];
                },
                $requestData[JsonApiDoc::INCLUDED]
            )
        );
    }

    /**
     * @return \Generator
     */
    public function includedDataProvider()
    {
        $reqProd = RequestProduct::class;
        $prodItem = RequestProductItem::class;

        yield 'empty included data' => [
            'includedData' => [],
            'expected' => []
        ];

        yield 'one item' => [
            'includedData' => [$prodItem],
            'expected' => [$prodItem]
        ];

        yield 'not sorted two items' => [
            'includedData' => [$reqProd, $prodItem],
            'expected' => [$prodItem, $reqProd]
        ];

        yield 'sorted two items' => [
            'includedData' => [$prodItem, $reqProd],
            'expected' => [$prodItem, $reqProd]
        ];

        yield 'not sorted items' => [
            'includedData' => [$prodItem, $reqProd, $prodItem, $reqProd],
            'expected' => [$prodItem, $prodItem, $reqProd, $reqProd]
        ];
    }

    /**
     * @param array $includedData
     * @return CreateContext|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createContext(array $includedData)
    {
        $requestData[JsonApiDoc::INCLUDED] = array_map(
            function ($type) {
                return [JsonApiDoc::TYPE => $type];
            },
            $includedData
        );

        /** @var CreateContext|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMockBuilder(CreateContext::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRequestType'])->getMock();
        $context->expects($this->any())->method('getRequestType')
            ->willReturn($this->createMock(RequestType::class));

        $context->setRequestData($requestData);

        return $context;
    }
}

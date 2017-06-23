<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApi;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Processor\Shared\ProcessUnitPrecisions;

class ProcessUnitPrecisionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var CreateContextStub|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var ProcessUnitPrecisions
     */
    protected $processUnitPrecisions;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configProvider   = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder(MetadataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context = new CreateContextStub($configProvider, $metadataProvider);

        $this->processUnitPrecisions = new ProcessUnitPrecisionsStub($this->doctrineHelper);
    }

    public function testProcess()
    {
        $this->context->setRequestData($this->createRequestData());
        $this->processUnitPrecisions->process($this->context);
        $relationships = $this->context->getRequestData()['relationships'];
        $this->assertArrayHasKey(
            JsonApi::ID,
            $relationships[ProcessUnitPrecisions::PRIMARY_UNIT_PRECISION][JsonApi::DATA]
        );
        $this->assertArrayHasKey(
            JsonApi::TYPE,
            $relationships[ProcessUnitPrecisions::PRIMARY_UNIT_PRECISION][JsonApi::DATA]
        );
        $this->assertArrayHasKey(
            JsonApi::ID,
            $relationships[ProcessUnitPrecisions::UNIT_PRECISIONS][JsonApi::DATA][0]
        );
        $this->assertArrayHasKey(
            JsonApi::ID,
            $relationships[ProcessUnitPrecisions::UNIT_PRECISIONS][JsonApi::DATA][1]
        );
    }

    public function testProcessNoUnitPrecisions()
    {
        $requestData = $this->createRequestData();
        unset($requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS][ProcessUnitPrecisions::UNIT_PRECISIONS]);
        $this->context->setRequestData($requestData);
        $this->processUnitPrecisions->process($this->context);
        $this->assertSame($requestData, $this->context->getRequestData());
    }

    /**
     * @return array
     */
    private function createRequestData()
    {
        return [
            "data" => [
                "type" => "products",
                "attributes" => [
                    "sku" => "test-api",
                    "status" => "enabled",
                    "variantFields" => [],
                    "createdAt" => "2017-06-13T07:12:06Z",
                    "updatedAt" => "2017-06-13T07:12:31Z",
                    "productType" => "simple",
                    "featured" => true
                ],
                "relationships" => [
                    "primaryUnitPrecision" => [
                        "unit_code" => "piece",
                    ],
                    "unitPrecisions" => [
                        "data" => [
                            0 => [
                                "type" => "productunitprecisions",
                                "unit_code" => "each",
                                "unit_precision" => "0",
                                "conversion_rate" => "2",
                                "sell" => "1"
                            ],
                            1 => [
                                "type" => "productunitprecisions",
                                "unit_code" => "item",
                                "unit_precision" => "0",
                                "conversion_rate" => "2",
                                "sell" => "1"
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}

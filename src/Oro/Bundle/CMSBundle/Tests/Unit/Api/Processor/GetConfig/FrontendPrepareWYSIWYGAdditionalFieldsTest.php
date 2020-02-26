<?php

namespace commerce\src\Oro\Bundle\CMSBundle\Tests\Unit\Api\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\CMSBundle\Api\Processor\GetConfig\FrontendPrepareWYSIWYGAdditionalFields;
use Oro\Bundle\CMSBundle\Provider\WYSIWYGFieldsProvider;
use PHPUnit\Framework\MockObject\MockObject;

class FrontendPrepareWYSIWYGAdditionalFieldsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var WYSIWYGFieldsProvider|MockObject
     */
    private $wysiwygFieldsProvider;

    /**
     * @var FrontendPrepareWYSIWYGAdditionalFields
     */
    private $processor;

    protected function setUp()
    {
        $this->wysiwygFieldsProvider = $this->createMock(WYSIWYGFieldsProvider::class);

        $this->processor = new FrontendPrepareWYSIWYGAdditionalFields($this->wysiwygFieldsProvider);
    }

    public function testProcessWithoutWYSIWYGFAttributeFields()
    {
        $context = new ConfigContext();
        $context->setResult($this->getEntityDefinitionConfig([
            'productAttributes',
            'wysiwygField'
        ]));
        $context->setClassName('\stdClass');

        $this->wysiwygFieldsProvider->expects($this->once())
            ->method('getWysiwygAttributes')
            ->with('\stdClass')
            ->willReturn([]);

        $this->processor->process($context);

        $this->assertEquals(
            [
                'fields' => [
                    'wysiwygField' => null,
                    'productAttributes' => null
                ]
            ],
            $context->getResult()->toArray()
        );
    }

    public function testProcess()
    {
        $context = new ConfigContext();
        $context->setResult($this->getEntityDefinitionConfig([
            'productAttributes',
            'wysiwygField'
        ]));
        $context->setClassName('\stdClass');

        $this->wysiwygFieldsProvider->expects($this->once())
            ->method('getWysiwygAttributes')
            ->with('\stdClass')
            ->willReturn(['wysiwygField']);

        $this->processor->process($context);

        $this->assertEquals(
            [
                'fields' => [
                    'wysiwygField' => [
                        'exclude' => true
                    ],
                    'productAttributes' => [
                        'depends_on' => ['wysiwygField']
                    ]
                ]
            ],
            $context->getResult()->toArray()
        );
    }

    /**
     * @param string[] $fields
     *
     * @return EntityDefinitionConfig
     */
    private function getEntityDefinitionConfig(array $fields = [])
    {
        $config = new EntityDefinitionConfig();
        foreach ($fields as $field) {
            $config->addField($field);
        }

        return $config;
    }
}

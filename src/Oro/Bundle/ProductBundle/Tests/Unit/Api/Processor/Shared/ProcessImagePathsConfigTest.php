<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor\Shared;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\ProductBundle\Api\Processor\Shared\ProcessImagePathsConfig;

class ProcessImagePathsConfigTest extends ConfigProcessorTestCase
{
    /**
     * @var ProcessImagePathsConfig
     */
    protected $processImagePathsConfig;

    protected function setUp()
    {
        parent::setUp();

        $this->processImagePathsConfig = new ProcessImagePathsConfig();
    }

    public function testProcess()
    {
        $this->context->setResult($this->createConfigObject([]));
        $this->processImagePathsConfig->process($this->context);

        self::assertEquals(
            [
                'fields' => [
                    ProcessImagePathsConfig::CONFIG_FILE_PATH => [
                        'data_type' => 'array'
                    ]
                ]
            ],
            $this->context->getResult()->toArray()
        );
    }
}

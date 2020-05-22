<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormContextStub;
use Oro\Bundle\CMSBundle\Api\Processor\AddAdditionalWYSIWYGFields;
use Oro\Bundle\CMSBundle\Provider\WYSIWYGFieldsProvider;
use Symfony\Component\Form\FormBuilderInterface;

class AddAdditionalWYSIWYGFieldsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var WYSIWYGFieldsProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $wysiwygFieldsProvider;

    /**
     * @var AddAdditionalWYSIWYGFields
     */
    private $processor;

    protected function setUp(): void
    {
        $this->wysiwygFieldsProvider = $this->createMock(WYSIWYGFieldsProvider::class);

        $this->processor = new AddAdditionalWYSIWYGFields($this->wysiwygFieldsProvider);
    }

    public function testProcess()
    {
        /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $formBuilder */
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $formBuilder->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['first', true],
                ['second', false]
            ]);

        $formBuilder->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                ['first_style'],
                ['first_properties']
            );

        /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject $configProvider */
        $configProvider = $this->createMock(ConfigProvider::class);

        /** @var MetadataProvider|\PHPUnit\Framework\MockObject\MockObject $metadataProvider */
        $metadataProvider = $this->createMock(MetadataProvider::class);

        $context = new FormContextStub($configProvider, $metadataProvider);

        $context->setFormBuilder($formBuilder);
        $context->setClassName('\stdClass');

        $this->wysiwygFieldsProvider->expects($this->once())
            ->method('getWysiwygFields')
            ->with('\stdClass')
            ->willReturn(['first', 'second']);


        $this->processor->process($context);
    }
}

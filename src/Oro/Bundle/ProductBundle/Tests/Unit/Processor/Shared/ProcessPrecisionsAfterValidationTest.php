<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Processor\Shared;

use Symfony\Component\Form\Form;

use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormContextStub;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Processor\Shared\ProcessPrecisionsAfterValidation;

class ProcessPrecisionsAfterValidationTest extends \PHPUnit_Framework_TestCase
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
     * @var ProcessPrecisionsAfterValidation
     */
    protected $processPrecisionsAfterValidation;

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
        $this->context = new FormContextStub($configProvider, $metadataProvider);

        $this->processPrecisionsAfterValidation = new ProcessPrecisionsAfterValidationStub($this->doctrineHelper);
    }

    public function testProcess()
    {
        $form = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->getMock();
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);
        $this->context->setForm($form);
        $this->processPrecisionsAfterValidation->process($this->context);
    }

    public function testProcessFormNotSubmitted()
    {
        $form = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->getMock();
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(false);
        $form->expects($this->never())
            ->method('isValid');
        $this->context->setForm($form);
        $this->processPrecisionsAfterValidation->process($this->context);
    }

    public function testProcessFormIsValid()
    {
        $form = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->getMock();
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $this->context->setForm($form);
        $this->processPrecisionsAfterValidation->process($this->context);
    }
}

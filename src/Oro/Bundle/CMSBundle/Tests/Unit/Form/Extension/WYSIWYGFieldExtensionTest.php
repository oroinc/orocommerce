<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CMSBundle\Form\Extension\WYSIWYGFieldExtension;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGPropertiesType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGStylesType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class WYSIWYGFieldExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var WYSIWYGFieldExtension
     */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new WYSIWYGFieldExtension();
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertEquals([WYSIWYGType::class], WYSIWYGFieldExtension::getExtendedTypes());
    }

    public function testBuildForm(): void
    {
        /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder
            ->expects($this->once())
            ->method('addEventListener');

        $this->extension->buildForm($builder, []);
    }

    public function testOnParentFormExists(): void
    {
        $parentForm = $this->createMock(FormInterface::class);
        $parentForm
            ->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                ['form_name_style', WYSIWYGStylesType::class],
                ['form_name_properties', WYSIWYGPropertiesType::class]
            );

        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('getParent')
            ->willReturn($parentForm);
        $form
            ->expects($this->once())
            ->method('getName')
            ->willReturn('form_name');

        /** @var FormEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(FormEvent::class);
        $event
            ->expects($this->once())
            ->method('getForm')
            ->willReturn($form);

        $this->extension->onPreSetData($event);
    }

    public function testOnParentFormNotExists(): void
    {
        $parentForm = $this->createMock(FormInterface::class);
        $parentForm
            ->expects($this->never())
            ->method('add');

        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('getParent')
            ->willReturn(null);

        /** @var FormEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(FormEvent::class);
        $event
            ->expects($this->once())
            ->method('getForm')
            ->willReturn($form);

        $this->extension->onPreSetData($event);
    }
}

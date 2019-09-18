<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CMSBundle\Form\Extension\WYSIWYGStylesExtension;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGStylesType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class WYSIWYGStylesExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var WYSIWYGStylesExtension
     */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new WYSIWYGStylesExtension();
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertEquals([WYSIWYGType::class], WYSIWYGStylesExtension::getExtendedTypes());
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
            ->expects($this->once())
            ->method('add')
            ->with('form_name_style', WYSIWYGStylesType::class);

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

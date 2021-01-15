<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CMSBundle\Form\Extension\DigitalAssetTwigTagsWysiwygExtension;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGStylesType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class DigitalAssetTwigTagsWysiwygExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var DigitalAssetTwigTagsConverter|\PHPUnit\Framework\MockObject\MockObject */
    private $digitalAssetTwigTagsConverter;

    /** @var DigitalAssetTwigTagsWysiwygExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->digitalAssetTwigTagsConverter = $this->createMock(DigitalAssetTwigTagsConverter::class);

        $this->extension = new DigitalAssetTwigTagsWysiwygExtension($this->digitalAssetTwigTagsConverter);
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertEquals(
            [WYSIWYGType::class, WYSIWYGStylesType::class],
            DigitalAssetTwigTagsWysiwygExtension::getExtendedTypes()
        );
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder
            ->expects($this->once())
            ->method('addViewTransformer')
            ->with($this->isInstanceOf(CallbackTransformer::class));

        $this->extension->buildForm($builder, []);
    }

    public function testOnPreSetDataWhenNoData(): void
    {
        $event = $this->createMock(FormEvent::class);
        $event
            ->expects($this->never())
            ->method('setData');

        $this->extension->onPreSetData($event);
    }

    public function testOnPreSetData(): void
    {
        $data = 'sample data';
        $convertedData = 'sample converted data';
        $event = new FormEvent($this->createMock(FormInterface::class), $data);

        $this->digitalAssetTwigTagsConverter
            ->expects($this->once())
            ->method('convertToUrls')
            ->with($data)
            ->willReturn($convertedData);

        $this->extension->onPreSetData($event);

        $this->assertEquals($convertedData, $event->getData());
    }

    public function testOnSubmitWhenNoData(): void
    {
        $event = $this->createMock(FormEvent::class);
        $event
            ->expects($this->never())
            ->method('setData');

        $this->extension->onSubmit($event);
    }

    public function testOnSubmit(): void
    {
        $data = 'sample data';
        $convertedData = 'sample converted data';
        $event = new FormEvent($this->createMock(FormInterface::class), $data);

        $this->digitalAssetTwigTagsConverter
            ->expects($this->once())
            ->method('convertToTwigTags')
            ->with($data)
            ->willReturn($convertedData);

        $this->extension->onSubmit($event);

        $this->assertEquals($convertedData, $event->getData());
    }
}

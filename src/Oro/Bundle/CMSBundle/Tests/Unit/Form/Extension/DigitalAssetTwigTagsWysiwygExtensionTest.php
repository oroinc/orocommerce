<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CMSBundle\Form\Extension\DigitalAssetTwigTagsWysiwygExtension;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGStylesType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;

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
}

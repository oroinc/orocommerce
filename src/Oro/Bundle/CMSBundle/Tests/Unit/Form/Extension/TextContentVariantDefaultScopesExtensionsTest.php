<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CMSBundle\Form\Extension\TextContentVariantDefaultScopesExtensions;
use Oro\Bundle\CMSBundle\Form\Type\TextContentVariantType;
use Symfony\Component\Form\FormBuilderInterface;

class TextContentVariantDefaultScopesExtensionsTest extends \PHPUnit\Framework\TestCase
{
    private TextContentVariantDefaultScopesExtensions $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->extension = new TextContentVariantDefaultScopesExtensions();
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertEquals(
            [TextContentVariantType::class],
            TextContentVariantDefaultScopesExtensions::getExtendedTypes()
        );
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder
            ->expects($this->once())
            ->method('addEventListener');

        $this->extension->buildForm($builder, []);
    }
}

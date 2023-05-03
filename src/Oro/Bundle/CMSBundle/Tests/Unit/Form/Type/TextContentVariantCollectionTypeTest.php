<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Form\Type\TextContentVariantCollectionType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextContentVariantCollectionTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var TextContentVariantCollectionType */
    private $type;

    protected function setUp(): void
    {
        $this->type = new TextContentVariantCollectionType();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->any())
            ->method('setDefault')
            ->withConsecutive(
                ['prototype_name', '__variant_idx__']
            );
        $this->type->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(CollectionType::class, $this->type->getParent());
    }
}

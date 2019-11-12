<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Form\Type\ImageSlideCollectionType;
use Oro\Bundle\CMSBundle\Form\Type\ImageSlideType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImageSlideCollectionTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var ImageSlideCollectionType */
    private $type;

    protected function setUp(): void
    {
        $this->type = new ImageSlideCollectionType();
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['entry_type' => ImageSlideType::class]);

        $this->type->configureOptions($resolver);
    }

    public function testGetParent(): void
    {
        $this->assertEquals(CollectionType::class, $this->type->getParent());
    }
}

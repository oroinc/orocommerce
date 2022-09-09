<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Form\Type\TabbedContentItemCollectionType;
use Oro\Bundle\CMSBundle\Form\Type\TabbedContentItemType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TabbedContentItemCollectionTypeTest extends \PHPUnit\Framework\TestCase
{
    private TabbedContentItemCollectionType $type;

    protected function setUp(): void
    {
        $this->type = new TabbedContentItemCollectionType();
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver
            ->expects(self::once())
            ->method('setDefaults')
            ->with(['entry_type' => TabbedContentItemType::class]);

        $this->type->configureOptions($resolver);
    }

    public function testGetBlockPrefix(): void
    {
        self::assertEquals('oro_cms_tabbed_content_item_collection', $this->type->getBlockPrefix());
    }

    public function testGetParent(): void
    {
        self::assertEquals(CollectionType::class, $this->type->getParent());
    }
}

<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentBlockView;

use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\CMSBundle\ContentBlock\ContentBlockResolver;
use Oro\Bundle\CMSBundle\ContentBlock\Model\ContentBlockView;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;

class ContentBlockResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContentBlockResolver */
    protected $resolver;

    protected function setUp()
    {
        $propertyAccessor = new PropertyAccessor;
        $this->resolver = new ContentBlockResolver($propertyAccessor);
    }

    public function testGetContentBlockView()
    {
        $block = new ContentBlock();
        $block->setAlias('block_alias');
        $block->setEnabled(true);
        $titles = $block->getTitles();
        $block->addScope(new ScopeStub(true, true));

        $variant1 = new TextContentVariant();
        $variant1->setDefault(true);
        $variant1->setContent('variant_1_content');
        $block->addContentVariant($variant1);

        $variant2 = new TextContentVariant();
        $variant2->setDefault(true);
        $variant2->setContent('variant2_content');
        $variant2->addScope(new ScopeStub(true, true));
        $block->addContentVariant($variant2);

        $view = $this->resolver->getContentBlockView($block, ['field1' => true, 'field2' => true]);
        $this->assertInstanceOf(ContentBlockView::class, $view);
        $this->assertSame('variant2_content', $view->getContent());
        $this->assertEquals('block_alias', $view->getAlias());
        $this->assertSame($titles, $view->getTitles());
    }

    public function testGetContentBlockViewNotEnabledContentBlock()
    {
        $block = new ContentBlock();
        $block->setEnabled(false);
        $this->assertNull($this->resolver->getContentBlockView($block, []));
    }

    public function testGetContentBlockViewWithoutScopes()
    {
        $block = new ContentBlock();
        $block->setEnabled(true);
        $variant = new TextContentVariant();
        $variant->setDefault(true);
        $variant->setContent('variant_content');
        $block->addContentVariant($variant);
        $view = $this->resolver->getContentBlockView($block, []);
        $this->assertInstanceOf(ContentBlockView::class, $view);
        $this->assertSame('variant_content', $view->getContent());
    }

    public function testGetContentBlockViewWithoutSuitableScope()
    {
        $block = new ContentBlock();
        $block->setEnabled(true);
        $block->addScope(new ScopeStub(true, true));
        $this->assertNull($this->resolver->getContentBlockView($block, ['field1' => false, 'field2' => true]));
    }

    public function testGetContentBlockViewWithoutSuitableVariant()
    {
        $block = new ContentBlock();
        $block->setEnabled(true);
        $block->addScope(new ScopeStub(true, true));

        $variant1 = new TextContentVariant();
        $variant1->setDefault(true);
        $variant1->setContent('variant_1_content');
        $block->addContentVariant($variant1);

        $variant2 = new TextContentVariant();
        $variant2->setDefault(true);
        $variant2->setContent('variant2_content');
        $variant2->addScope(new ScopeStub(true, false));
        $block->addContentVariant($variant2);

        $view = $this->resolver->getContentBlockView($block, ['field1' => true, 'field2' => true]);
        $this->assertInstanceOf(ContentBlockView::class, $view);
        $this->assertSame('variant_1_content', $view->getContent());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Default content variant should be defined.
     */
    public function testGetContentBlockViewWithoutDefaultVariant()
    {
        $block = new ContentBlock();
        $block->setEnabled(true);
        $block->addScope(new ScopeStub(true, true));

        $variant1 = new TextContentVariant();
        $variant1->setContent('variant_1_content');
        $block->addContentVariant($variant1);

        $variant2 = new TextContentVariant();
        $variant2->setContent('variant2_content');
        $variant2->addScope(new ScopeStub(true, false));
        $block->addContentVariant($variant2);

        $this->resolver->getContentBlockView($block, ['field1' => true, 'field2' => true]);
    }
}

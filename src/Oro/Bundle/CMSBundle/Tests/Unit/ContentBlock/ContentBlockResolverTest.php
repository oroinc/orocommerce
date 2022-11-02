<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentBlock;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\ContentBlock\ContentBlockResolver;
use Oro\Bundle\CMSBundle\ContentBlock\Model\ContentBlockView;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\Repository\ContentBlockRepository;
use Oro\Bundle\CMSBundle\Entity\Repository\TextContentVariantRepository;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

class ContentBlockResolverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var ContentBlockResolver
     */
    protected $resolver;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->resolver = new ContentBlockResolver($this->registry);
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
        $variant1->setContentStyle('h1 {color: #fff}');
        $block->addContentVariant($variant1);

        $variant2 = new TextContentVariant();
        $variant2->setDefault(true);
        $variant2->setContent('variant2_content');
        $scope = new ScopeStub(true, true);
        $variant2->addScope($scope);
        $variant2->setContentStyle('h1 {color: #000}');
        $block->addContentVariant($variant2);

        /** @var ScopeCriteria $criteria */
        $criteria = $this->createMock(ScopeCriteria::class);

        $variantRepo = $this->createMock(TextContentVariantRepository::class);
        $variantRepo->expects($this->once())
            ->method('getMatchingVariantForBlockByCriteria')
            ->with($block, $criteria)
            ->willReturn($variant2);
        $variantEm = $this->createMock(EntityManagerInterface::class);
        $variantEm->expects($this->once())
            ->method('getRepository')
            ->with(TextContentVariant::class)
            ->willReturn($variantRepo);

        $blockRepo = $this->createMock(ContentBlockRepository::class);
        $blockRepo->expects($this->once())
            ->method('getMostSuitableScope')
            ->with($block, $criteria)
            ->willReturn($scope);
        $blockEm = $this->createMock(EntityManagerInterface::class);
        $blockEm->expects($this->once())
            ->method('getRepository')
            ->with(ContentBlock::class)
            ->willReturn($blockRepo);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap([
                [TextContentVariant::class, $variantEm],
                [ContentBlock::class, $blockEm]
            ]);

        $view = $this->resolver->getContentBlockViewByCriteria($block, $criteria);
        $this->assertInstanceOf(ContentBlockView::class, $view);
        $this->assertSame('variant2_content', $view->getContent());
        $this->assertSame('h1 {color: #000}', $view->getContentStyle());
        $this->assertEquals('block_alias', $view->getAlias());
        $this->assertSame($titles, $view->getTitles());
    }

    public function testGetContentBlockViewNotEnabledContentBlock()
    {
        $block = new ContentBlock();
        $block->setAlias('block_alias');
        $block->setEnabled(false);
        /** @var ScopeCriteria $criteria */
        $criteria = $this->createMock(ScopeCriteria::class);
        $this->assertNull($this->resolver->getContentBlockViewByCriteria($block, $criteria));
    }

    public function testGetContentBlockViewWithoutScopes()
    {
        $block = new ContentBlock();
        $block->setAlias('block_alias');
        $block->setEnabled(true);
        $variant = new TextContentVariant();
        $variant->setDefault(true);
        $variant->setContent('variant_content');
        $block->addContentVariant($variant);
        /** @var ScopeCriteria $criteria */
        $criteria = $this->createMock(ScopeCriteria::class);

        $variantRepo = $this->createMock(TextContentVariantRepository::class);
        $variantRepo->expects($this->once())
            ->method('getMatchingVariantForBlockByCriteria')
            ->with($block, $criteria)
            ->willReturn($variant);
        $variantEm = $this->createMock(EntityManagerInterface::class);
        $variantEm->expects($this->once())
            ->method('getRepository')
            ->with(TextContentVariant::class)
            ->willReturn($variantRepo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(TextContentVariant::class)
            ->willReturn($variantEm);

        $view = $this->resolver->getContentBlockViewByCriteria($block, $criteria);

        $this->assertInstanceOf(ContentBlockView::class, $view);
        $this->assertSame('variant_content', $view->getContent());
    }

    public function testGetContentBlockViewWithoutSuitableScope()
    {
        $block = new ContentBlock();
        $block->setAlias('block_alias');
        $block->setEnabled(true);
        $scope = new ScopeStub(true, true);
        $block->addScope($scope);
        /** @var ScopeCriteria $criteria */
        $criteria = $this->createMock(ScopeCriteria::class);

        $blockRepo = $this->createMock(ContentBlockRepository::class);
        $blockRepo->expects($this->once())
            ->method('getMostSuitableScope')
            ->with($block, $criteria)
            ->willReturn(null);
        $blockEm = $this->createMock(EntityManagerInterface::class);
        $blockEm->expects($this->once())
            ->method('getRepository')
            ->with(ContentBlock::class)
            ->willReturn($blockRepo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ContentBlock::class)
            ->willReturn($blockEm);

        $this->assertNull($this->resolver->getContentBlockViewByCriteria($block, $criteria));
    }

    public function testGetContentBlockViewWithoutSuitableVariant()
    {
        $block = new ContentBlock();
        $block->setAlias('block_alias');
        $block->setEnabled(true);
        $scope = new ScopeStub(true, true);
        $block->addScope($scope);

        $variant1 = new TextContentVariant();
        $variant1->setDefault(true);
        $variant1->setContent('variant_1_content');
        $block->addContentVariant($variant1);

        $variant2 = new TextContentVariant();
        $variant2->setDefault(true);
        $variant2->setContent('variant2_content');
        $variant2->addScope(new ScopeStub(true, false));
        $block->addContentVariant($variant2);
        /** @var ScopeCriteria $criteria */
        $criteria = $this->createMock(ScopeCriteria::class);

        $variantRepo = $this->createMock(TextContentVariantRepository::class);
        $variantRepo->expects($this->once())
            ->method('getMatchingVariantForBlockByCriteria')
            ->with($block, $criteria)
            ->willReturn(null);
        $variantRepo->expects($this->once())
            ->method('getDefaultContentVariantForContentBlock')
            ->with($block)
            ->willReturn($variant1);
        $variantEm = $this->createMock(EntityManagerInterface::class);
        $variantEm->expects($this->once())
            ->method('getRepository')
            ->with(TextContentVariant::class)
            ->willReturn($variantRepo);

        $blockRepo = $this->createMock(ContentBlockRepository::class);
        $blockRepo->expects($this->once())
            ->method('getMostSuitableScope')
            ->with($block, $criteria)
            ->willReturn($scope);
        $blockEm = $this->createMock(EntityManagerInterface::class);
        $blockEm->expects($this->once())
            ->method('getRepository')
            ->with(ContentBlock::class)
            ->willReturn($blockRepo);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap([
                [TextContentVariant::class, $variantEm],
                [ContentBlock::class, $blockEm]
            ]);

        $view = $this->resolver->getContentBlockViewByCriteria($block, $criteria);
        $this->assertInstanceOf(ContentBlockView::class, $view);
        $this->assertSame('variant_1_content', $view->getContent());
    }

    public function testGetContentBlockViewWithoutDefaultVariant()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Default content variant should be defined.');

        $block = new ContentBlock();
        $block->setAlias('block_alias');
        $block->setEnabled(true);
        $scope = new ScopeStub(true, true);
        $block->addScope($scope);

        $variant1 = new TextContentVariant();
        $variant1->setContent('variant_1_content');
        $block->addContentVariant($variant1);

        $variant2 = new TextContentVariant();
        $variant2->setContent('variant2_content');
        $variant2->addScope(new ScopeStub(true, false));
        $block->addContentVariant($variant2);

        /** @var ScopeCriteria $criteria */
        $criteria = $this->createMock(ScopeCriteria::class);

        $variantRepo = $this->createMock(TextContentVariantRepository::class);
        $variantRepo->expects($this->once())
            ->method('getMatchingVariantForBlockByCriteria')
            ->with($block, $criteria)
            ->willReturn(null);
        $variantRepo->expects($this->once())
            ->method('getDefaultContentVariantForContentBlock')
            ->with($block)
            ->willReturn(null);
        $variantEm = $this->createMock(EntityManagerInterface::class);
        $variantEm->expects($this->once())
            ->method('getRepository')
            ->with(TextContentVariant::class)
            ->willReturn($variantRepo);

        $blockRepo = $this->createMock(ContentBlockRepository::class);
        $blockRepo->expects($this->once())
            ->method('getMostSuitableScope')
            ->with($block, $criteria)
            ->willReturn($scope);
        $blockEm = $this->createMock(EntityManagerInterface::class);
        $blockEm->expects($this->once())
            ->method('getRepository')
            ->with(ContentBlock::class)
            ->willReturn($blockRepo);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap([
                [TextContentVariant::class, $variantEm],
                [ContentBlock::class, $blockEm]
            ]);

        $this->resolver->getContentBlockViewByCriteria($block, $criteria);
    }
}

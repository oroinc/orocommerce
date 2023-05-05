<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CMSBundle\ContentBlock\ContentBlockResolver;
use Oro\Bundle\CMSBundle\ContentBlock\Model\ContentBlockView;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Layout\DataProvider\ContentBlockDataProvider;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class ContentBlockDataProviderTest extends \PHPUnit\Framework\TestCase
{
    const ENTITY_CLASS = 'TestEntityClass';
    const SCOPE_TYPE = 'test_scope_type';

    private ContentBlockDataProvider $provider;

    protected ContentBlockResolver|MockObject $resolver;

    protected ManagerRegistry|MockObject $registry;

    protected ScopeManager|MockObject $scopeManager;

    protected LoggerInterface|MockObject $logger;

    protected function setUp(): void
    {
        $this->resolver = $this->createMock(ContentBlockResolver::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->provider = new ContentBlockDataProvider(
            $this->resolver,
            $this->registry,
            $this->scopeManager,
            $this->logger,
            self::ENTITY_CLASS,
            self::SCOPE_TYPE
        );
    }

    public function testGetContentBlockView(): void
    {
        $context = ['field' => 'value'];

        $contentBlock = new ContentBlock();
        $view = $this->createMock(ContentBlockView::class);
        $criteria = new ScopeCriteria($context, $this->createMock(ClassMetadataFactory::class));

        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with(self::SCOPE_TYPE)
            ->willReturn($criteria);

        $this->resolver->expects($this->once())
            ->method('getContentBlockViewByCriteria')
            ->with($contentBlock, $criteria)
            ->willReturn($view);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['alias' => 'test_alias'])
            ->willReturn($contentBlock);
        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->once())
            ->method('getRepository')
            ->with(self::ENTITY_CLASS)
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($manager);

        $this->assertEquals($view, $this->provider->getContentBlockView('test_alias'));
    }

    public function testGetContentBlockViewWithWrongAlias(): void
    {
        $context = ['field' => 'value'];
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with(self::SCOPE_TYPE)
            ->willReturn(new ScopeCriteria($context, $this->createMock(ClassMetadataFactory::class)));

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['alias' => 'test_alias'])
            ->willReturn(null);
        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->once())
            ->method('getRepository')
            ->with(self::ENTITY_CLASS)
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($manager);

        $this->assertNull($this->provider->getContentBlockView('test_alias'));
    }

    public function testGetContentBlockViewNotVisible(): void
    {
        $context = ['field' => 'value'];

        $contentBlock = new ContentBlock();
        $criteria = new ScopeCriteria($context, $this->createMock(ClassMetadataFactory::class));

        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with(self::SCOPE_TYPE)
            ->willReturn($criteria);

        $this->resolver->expects($this->once())
            ->method('getContentBlockViewByCriteria')
            ->with($contentBlock, $criteria)
            ->willReturn(null);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['alias' => 'test_alias'])
            ->willReturn($contentBlock);
        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->once())
            ->method('getRepository')
            ->with(self::ENTITY_CLASS)
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($manager);

        $this->logger->expects($this->once())
            ->method('notice')
            ->with('Content block with alias "{alias}" is not visible to user.', ['alias' => 'test_alias']);

        $this->assertNull($this->provider->getContentBlockView('test_alias'));
    }
}

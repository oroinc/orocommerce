<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Psr\Log\LoggerInterface;

use Oro\Bundle\CMSBundle\ContentBlock\ContentBlockResolver;
use Oro\Bundle\CMSBundle\ContentBlock\Model\ContentBlockView;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Layout\DataProvider\ContentBlockDataProvider;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

class ContentBlockDataProviderTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'TestEntityClass';
    const SCOPE_TYPE = 'test_scope_type';

    /** @var ContentBlockDataProvider */
    private $provider;

    /** @var ContentBlockResolver|\PHPUnit_Framework_MockObject_MockObject */
    protected $resolver;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var ScopeManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $scopeManager;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    protected function setUp()
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

    public function testGetContentBlockView()
    {
        $context = ['field' => 'value'];

        $contentBlock = new ContentBlock();
        $view = $this->createMock(ContentBlockView::class);

        $this->resolver->expects($this->once())
            ->method('getContentBlockView')
            ->with($contentBlock)
            ->willReturn($view);

        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with(self::SCOPE_TYPE)
            ->willReturn(new ScopeCriteria($context, []));

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

    public function testGetContentBlockViewWithWrongAlias()
    {
        $context = ['field' => 'value'];
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with(self::SCOPE_TYPE)
            ->willReturn(new ScopeCriteria($context, []));

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
        $this->logger->expects($this->once())
            ->method('notice')
            ->with('Content block with alias "{alias}" doesn\'t exists', ['alias' => 'test_alias']);

        $this->assertNull($this->provider->getContentBlockView('test_alias'));
    }
}

<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\ContentBlock\ContentBlockResolver;
use Oro\Bundle\CMSBundle\ContentBlock\Model\ContentBlockView;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\Repository\ContentBlockRepository;
use Oro\Bundle\CMSBundle\Layout\DataProvider\ContentBlockDataProvider;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ContentBlockDataProviderTest extends TestCase
{
    private const string SCOPE_TYPE = 'test_scope_type';

    private ContentBlockResolver&MockObject $resolver;
    private ManagerRegistry&MockObject $doctrine;
    private ScopeManager&MockObject $scopeManager;
    private ThemeConfigurationProvider&MockObject $themeConfigurationProvider;
    private AclHelper&MockObject $aclHelper;
    private LoggerInterface&MockObject $logger;
    private ContentBlockDataProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->resolver = $this->createMock(ContentBlockResolver::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->themeConfigurationProvider = $this->createMock(ThemeConfigurationProvider::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->provider = new ContentBlockDataProvider(
            $this->resolver,
            $this->doctrine,
            $this->scopeManager,
            $this->themeConfigurationProvider,
            $this->aclHelper,
            $this->logger,
            self::SCOPE_TYPE
        );
    }

    public function testGetPromotionalBlockAlias(): void
    {
        $alias = 'alias';

        $this->themeConfigurationProvider->expects(self::once())
            ->method('getThemeConfigurationOption')
            ->with(ThemeConfiguration::buildOptionKey('header', 'promotional_content'))
            ->willReturn(1);

        $repo = $this->createMock(ContentBlockRepository::class);
        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->with(ContentBlock::class)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('getContentBlockAliasById')
            ->with(1, $this->aclHelper)
            ->willReturn($alias);

        self::assertEquals($alias, $this->provider->getPromotionalBlockAlias());
    }

    public function testGetContentBlockAlias(): void
    {
        $alias = 'alias';
        $key = ThemeConfiguration::buildOptionKey('header', 'promotional_content');

        $this->themeConfigurationProvider->expects(self::once())
            ->method('getThemeConfigurationOption')
            ->with($key)
            ->willReturn(1);

        $repo = $this->createMock(ContentBlockRepository::class);
        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->with(ContentBlock::class)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('getContentBlockAliasById')
            ->with(1, $this->aclHelper)
            ->willReturn($alias);

        self::assertEquals($alias, $this->provider->getContentBlockAliasByThemeConfigKey($key));
    }

    public function testGetPromotionalBlockAliasWhenAliasNotExist(): void
    {
        $this->themeConfigurationProvider->expects(self::once())
            ->method('getThemeConfigurationOption')
            ->with(ThemeConfiguration::buildOptionKey('header', 'promotional_content'))
            ->willReturn(1);

        $repo = $this->createMock(ContentBlockRepository::class);
        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->with(ContentBlock::class)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('getContentBlockAliasById')
            ->with(1, $this->aclHelper)
            ->willReturn(null);

        self::assertSame('', $this->provider->getPromotionalBlockAlias());
    }

    public function testGetPromotionalBlockAliasWhenPromotionalBlockNotSelected(): void
    {
        $this->themeConfigurationProvider->expects(self::once())
            ->method('getThemeConfigurationOption')
            ->with(ThemeConfiguration::buildOptionKey('header', 'promotional_content'))
            ->willReturn(null);

        $this->doctrine->expects(self::never())
            ->method('getRepository');

        self::assertSame('', $this->provider->getPromotionalBlockAlias());
    }

    public function testGetContentBlockAliasWhenNoThemeConfigurationOption(): void
    {
        $key = ThemeConfiguration::buildOptionKey('header', 'promotional_content');
        $this->themeConfigurationProvider->expects(self::once())
            ->method('getThemeConfigurationOption')
            ->with($key)
            ->willReturn(null);

        $this->doctrine->expects(self::never())
            ->method('getRepository');

        self::assertSame('', $this->provider->getContentBlockAliasByThemeConfigKey($key));
    }

    public function testGetContentBlockView(): void
    {
        $context = ['field' => 'value'];

        $contentBlock = new ContentBlock();
        $view = $this->createMock(ContentBlockView::class);
        $criteria = new ScopeCriteria($context, $this->createMock(ClassMetadataFactory::class));

        $this->scopeManager->expects(self::once())
            ->method('getCriteria')
            ->with(self::SCOPE_TYPE)
            ->willReturn($criteria);

        $this->resolver->expects(self::once())
            ->method('getContentBlockViewByCriteria')
            ->with($contentBlock, $criteria)
            ->willReturn($view);

        $repo = $this->createMock(ContentBlockRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(ContentBlock::class)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['alias' => 'test_alias'])
            ->willReturn($contentBlock);

        self::assertEquals($view, $this->provider->getContentBlockView('test_alias'));
    }

    public function testGetContentBlockViewWithWrongAlias(): void
    {
        $this->scopeManager->expects(self::never())
            ->method('getCriteria');

        $repo = $this->createMock(ContentBlockRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(ContentBlock::class)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['alias' => 'test_alias'])
            ->willReturn(null);

        self::assertNull($this->provider->getContentBlockView('test_alias'));
    }

    public function testGetContentBlockViewNotVisible(): void
    {
        $context = ['field' => 'value'];

        $contentBlock = new ContentBlock();
        $criteria = new ScopeCriteria($context, $this->createMock(ClassMetadataFactory::class));

        $this->scopeManager->expects(self::once())
            ->method('getCriteria')
            ->with(self::SCOPE_TYPE)
            ->willReturn($criteria);

        $this->resolver->expects(self::once())
            ->method('getContentBlockViewByCriteria')
            ->with($contentBlock, $criteria)
            ->willReturn(null);

        $repo = $this->createMock(ContentBlockRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(ContentBlock::class)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['alias' => 'test_alias'])
            ->willReturn($contentBlock);

        $this->logger->expects(self::once())
            ->method('notice')
            ->with('Content block with alias "{alias}" is not visible to user.', ['alias' => 'test_alias']);

        self::assertNull($this->provider->getContentBlockView('test_alias'));
    }
}

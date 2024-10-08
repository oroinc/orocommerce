<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Provider\EmptySearchResultPageContentVariantProvider;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentScopeProvider;
use Oro\Bundle\WebCatalogBundle\Tests\Unit\Entity\Stub\ContentNode as ContentNodeStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmptySearchResultPageContentVariantProviderTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private ConfigManager|MockObject $configManager;

    private ContentNodeTreeResolverInterface|MockObject $contentNodeTreeResolver;

    private RequestWebContentScopeProvider|MockObject $requestWebContentScopeProvider;

    private EmptySearchResultPageContentVariantProvider $emptySearchResultPageContentVariantProvider;

    private ContentNodeRepository|MockObject $contentNodeRepo;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->requestWebContentScopeProvider = $this->createMock(RequestWebContentScopeProvider::class);
        $this->contentNodeTreeResolver = $this->createMock(ContentNodeTreeResolverInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);

        $this->emptySearchResultPageContentVariantProvider = new EmptySearchResultPageContentVariantProvider(
            $this->configManager,
            $this->contentNodeTreeResolver,
            $this->requestWebContentScopeProvider
        );

        $this->emptySearchResultPageContentVariantProvider->setDoctrine($doctrine);

        $this->setUpLoggerMock($this->emptySearchResultPageContentVariantProvider);

        $this->contentNodeRepo = $this->createMock(ContentNodeRepository::class);
        $doctrine
            ->method('getRepository')
            ->with(ContentNode::class)
            ->willReturn($this->contentNodeRepo);
    }

    public function testGetResolvedContentVariantNoEmptySearchResultPageConfigurationValue(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_web_catalog.empty_search_result_page')
            ->willReturn(null);

        $this->requestWebContentScopeProvider->expects(self::never())
            ->method('getScopes');

        $this->contentNodeTreeResolver->expects(self::never())
            ->method('getResolvedContentNode')
            ->withAnyParameters();

        self::assertNull($this->emptySearchResultPageContentVariantProvider->getResolvedContentVariant());
    }

    public function testGetResolvedContentVariantNoContentNode(): void
    {
        $contentNodeId = 42;
        $emptySearchResultPageKey = 'oro_web_catalog.empty_search_result_page';

        $this->configManager->expects(self::once())
            ->method('get')
            ->with($emptySearchResultPageKey)
            ->willReturn($contentNodeId);

        $this->contentNodeRepo
            ->expects(self::once())
            ->method('find')
            ->with($contentNodeId)
            ->willReturn(null);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Content node #{id} (fetched from "{system_config}" system config) '
                . 'for the empty search result page is not found',
                [
                    'id' => $contentNodeId,
                    'system_config' => $emptySearchResultPageKey,
                ]
            );

        $this->requestWebContentScopeProvider->expects(self::never())
            ->method('getScopes');

        $this->contentNodeTreeResolver->expects(self::never())
            ->method('getResolvedContentNode')
            ->withAnyParameters();

        self::assertNull($this->emptySearchResultPageContentVariantProvider->getResolvedContentVariant());
    }

    public function testGetResolvedContentVariantNoResolvedContentNode(): void
    {
        $contentNodeId = 1;
        $contentNode = (new ContentNodeStub())
            ->setId($contentNodeId);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_web_catalog.empty_search_result_page')
            ->willReturn($contentNodeId);

        $this->contentNodeRepo
            ->expects(self::once())
            ->method('find')
            ->with($contentNodeId)
            ->willReturn($contentNode);

        $scopes = [new Scope()];
        $this->requestWebContentScopeProvider->expects(self::once())
            ->method('getScopes')
            ->willReturn($scopes);

        $this->contentNodeTreeResolver->expects(self::once())
            ->method('getResolvedContentNode')
            ->with($contentNode, $scopes, ['tree_depth' => 0])
            ->willReturn(null);

        self::assertNull($this->emptySearchResultPageContentVariantProvider->getResolvedContentVariant());
    }

    public function testGetResolvedContentVariant(): void
    {
        $contentNodeId = 1;
        $contentNode = (new ContentNodeStub())
            ->setId($contentNodeId);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_web_catalog.empty_search_result_page')
            ->willReturn($contentNodeId);

        $this->contentNodeRepo
            ->expects(self::once())
            ->method('find')
            ->with($contentNodeId)
            ->willReturn($contentNode);

        $scopes = [new Scope()];
        $this->requestWebContentScopeProvider->expects(self::once())
            ->method('getScopes')
            ->willReturn($scopes);

        $resolvedContentVariant = $this->createMock(ResolvedContentVariant::class);

        $resolvedContentNode = $this->createMock(ResolvedContentNode::class);
        $resolvedContentNode->expects(self::once())
            ->method('getResolvedContentVariant')
            ->willReturn($resolvedContentVariant);

        $this->contentNodeTreeResolver->expects(self::once())
            ->method('getResolvedContentNode')
            ->with($contentNode, $scopes, ['tree_depth' => 0])
            ->willReturn($resolvedContentNode);

        self::assertSame(
            $resolvedContentVariant,
            $this->emptySearchResultPageContentVariantProvider->getResolvedContentVariant()
        );
    }

    public function testGetResolvedContentVariantNoDoctrine(): void
    {
        $contentNodeId = 1;

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_web_catalog.empty_search_result_page')
            ->willReturn($contentNodeId);

        $this->requestWebContentScopeProvider->expects(self::never())
            ->method('getScopes');

        $this->contentNodeTreeResolver->expects(self::never())
            ->method('getResolvedContentNode');

        $this->emptySearchResultPageContentVariantProvider->setDoctrine(null);

        self::assertNull($this->emptySearchResultPageContentVariantProvider->getResolvedContentVariant());
    }
}

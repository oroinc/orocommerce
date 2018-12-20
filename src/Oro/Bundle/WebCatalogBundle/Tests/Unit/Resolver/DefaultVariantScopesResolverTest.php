<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Resolver;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Provider\ScopeWebCatalogProvider;
use Oro\Bundle\WebCatalogBundle\Resolver\DefaultVariantScopesResolver;
use Oro\Component\Testing\Unit\EntityTrait;

class DefaultVariantScopesResolverTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $scopeManager;

    /**
     * @var DefaultVariantScopesResolver
     */
    protected $defaultVariantScopesResolver;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->defaultVariantScopesResolver = new DefaultVariantScopesResolver($this->registry, $this->scopeManager);
    }

    public function testResolve()
    {
        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => 1]);
        /** @var Scope $scope2 */
        $scope2 = $this->getEntity(Scope::class, ['id' => 2]);
        /** @var Scope $defaultScope */
        $defaultScope = $this->getEntity(Scope::class, ['id' => 3]);

        $webCatalog = $this->createMock(WebCatalog::class);
        $contentNode = new ContentNode();
        $contentNode->setWebCatalog($webCatalog);
        $contentNode->addScope($scope1);
        $contentNode->addScope($scope2);

        $defaultVariantLevel1 = new ContentVariant();
        $defaultVariantLevel1->setDefault(true);
        $defaultVariantLevel1->addScope($scope1);
        $defaultVariantLevel1->addScope($scope2);

        $variantLevel1 = new ContentVariant();
        $variantLevel1->setDefault(false);
        $variantLevel1->addScope($scope1);
        $variantLevel1->addScope($defaultScope);

        $contentNode->addContentVariant($defaultVariantLevel1);
        $contentNode->addContentVariant($variantLevel1);

        $defaultVariantLevel2 = new ContentVariant();
        $defaultVariantLevel2->setDefault(true);

        $childNode = new ContentNode();
        $childNode->setWebCatalog($webCatalog);
        $childNode->setParentNode($contentNode);
        $childNode->setParentScopeUsed(true);
        $childNode->addContentVariant($defaultVariantLevel2);

        $this->scopeManager->expects($this->any())
            ->method('findOrCreate')
            ->with(
                'web_content',
                [ScopeWebCatalogProvider::WEB_CATALOG => $webCatalog]
            )
            ->willReturn($defaultScope);

        $repository = $this->getMockBuilder(ContentNodeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->at(0))
            ->method('getDirectNodesWithParentScopeUsed')
            ->with($contentNode)
            ->willReturn([$childNode]);

        $repository->expects($this->at(1))
            ->method('getDirectNodesWithParentScopeUsed')
            ->with($childNode)
            ->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(ContentNode::class)
            ->willReturn($repository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ContentNode::class)
            ->willReturn($em);

        $this->defaultVariantScopesResolver->resolve($contentNode);

        $actualDefaultVariantLevel1Scopes = $contentNode->getDefaultVariant()->getScopes();
        $this->assertCount(1, $actualDefaultVariantLevel1Scopes);
        $this->assertContains($scope2, $actualDefaultVariantLevel1Scopes);

        $actualDefaultVariantLevel2Scopes = $childNode->getDefaultVariant()->getScopes();
        $this->assertCount(2, $actualDefaultVariantLevel2Scopes);
        $this->assertContains($scope1, $actualDefaultVariantLevel2Scopes);
        $this->assertContains($scope2, $actualDefaultVariantLevel2Scopes);

        $this->assertTrue($variantLevel1->getScopes()->contains($scope1));
        $this->assertFalse($variantLevel1->getScopes()->contains($defaultScope));
    }
}

<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Resolver;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Resolver\DefaultVariantScopesResolver;
use Oro\Component\Testing\Unit\EntityTrait;

class DefaultVariantScopesResolverTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var DefaultVariantScopesResolver
     */
    protected $defaultVariantScopesResolver;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->defaultVariantScopesResolver = new DefaultVariantScopesResolver($this->registry);
    }

    public function testResolve()
    {
        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => 1]);
        /** @var Scope $scope2 */
        $scope2 = $this->getEntity(Scope::class, ['id' => 2]);

        $contentNode = new ContentNode();
        $contentNode->addScope($scope1);
        $contentNode->addScope($scope2);

        $defaultVariantLevel1 = new ContentVariant();
        $defaultVariantLevel1->setDefault(true);
        $defaultVariantLevel1->addScope($scope1);
        $defaultVariantLevel1->addScope($scope2);

        $variantLevel1 = new ContentVariant();
        $variantLevel1->setDefault(false);
        $variantLevel1->addScope($scope1);

        $contentNode->addContentVariant($defaultVariantLevel1);
        $contentNode->addContentVariant($variantLevel1);

        $defaultVariantLevel2 = new ContentVariant();
        $defaultVariantLevel2->setDefault(true);

        $childNode = new ContentNode();
        $childNode->setParentNode($contentNode);
        $childNode->setParentScopeUsed(true);
        $childNode->addContentVariant($defaultVariantLevel2);

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
    }
}

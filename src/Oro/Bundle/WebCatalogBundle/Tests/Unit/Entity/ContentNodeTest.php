<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ContentNodeTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new ContentNode(), [
            ['parentNode', new ContentNode()],
            ['webCatalog', new WebCatalog()],
            ['materializedPath', 'path/to/node'],
            ['left', 30],
            ['level', 42],
            ['right', 20],
            ['root', 1],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
            ['parentScopeUsed', true],
            ['rewriteVariantTitle', true],
            ['slugPrototypesWithRedirect', new SlugPrototypesWithRedirect(new ArrayCollection(), false), false],
        ]);
        $this->assertPropertyCollections(new ContentNode(), [
            ['childNodes', new ContentNode()],
            ['titles', new LocalizedFallbackValue()],
            ['slugPrototypes', new LocalizedFallbackValue()],
            ['scopes', new Scope()],
            ['contentVariants', new ContentVariant()],
            ['localizedUrls', new LocalizedFallbackValue()]
        ]);
    }

    public function testIsUpdatedAtSet()
    {
        $entity = new ContentNode();
        $entity->setUpdatedAt(new \DateTime());

        $this->assertTrue($entity->isUpdatedAtSet());
    }

    public function testIsUpdatedAtNotSet()
    {
        $entity = new ContentNode();
        $entity->setUpdatedAt(null);

        $this->assertFalse($entity->isUpdatedAtSet());
    }

    public function testResetScopes()
    {
        $scope = new Scope();
        $contentNode = new ContentNode();
        $contentNode->addScope($scope);

        $this->assertNotEmpty($contentNode->getScopes());

        $contentNode->resetScopes();

        $this->assertEmpty($contentNode->getScopes());
    }

    public function testGetScopesConsideringParent()
    {
        $parentNodeScope = new Scope();
        $parentNode = new ContentNode();
        $parentNode->addScope($parentNodeScope);

        $node = new ContentNode();
        $node->setParentNode($parentNode);
        $node->setParentScopeUsed(true);

        $actualScopes = $node->getScopesConsideringParent();
        $this->assertCount(1, $actualScopes);
        $this->assertContains($parentNodeScope, $actualScopes);
    }
}

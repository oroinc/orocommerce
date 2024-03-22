<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CommerceMenuBundle\Entity\MenuUpdate;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ContentNodeTest extends TestCase
{
    use EntityTestCaseTrait;

    private PropertyAccessor $propertyAccessor;

    protected function setUp(): void
    {
        $this->propertyAccessor = new PropertyAccessor();
    }

    public function testAccessors(): void
    {
        self::assertPropertyAccessors(new ContentNode(), [
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
            ['referencedMenuItems', new ArrayCollection([new MenuUpdate()]), false],
            ['referencedConsents', new ArrayCollection([new Consent()]), false],
        ]);
        self::assertPropertyCollections(new ContentNode(), [
            ['childNodes', new ContentNode()],
            ['titles', new LocalizedFallbackValue()],
            ['slugPrototypes', new LocalizedFallbackValue()],
            ['scopes', new Scope()],
            ['contentVariants', new ContentVariant()],
            ['localizedUrls', new LocalizedFallbackValue()]
        ]);
    }

    public function testIsUpdatedAtSet(): void
    {
        $entity = new ContentNode();
        $entity->setUpdatedAt(new \DateTime());

        self::assertTrue($entity->isUpdatedAtSet());
    }

    public function testIsUpdatedAtNotSet(): void
    {
        $entity = new ContentNode();
        $entity->setUpdatedAt(null);

        self::assertFalse($entity->isUpdatedAtSet());
    }

    public function testResetScopes(): void
    {
        $scope = new Scope();
        $contentNode = new ContentNode();
        $contentNode->addScope($scope);

        self::assertNotEmpty($contentNode->getScopes());

        $contentNode->resetScopes();

        self::assertEmpty($contentNode->getScopes());
    }

    public function testGetScopesConsideringParent(): void
    {
        $parentNodeScope = new Scope();
        $parentNode = new ContentNode();
        $parentNode->addScope($parentNodeScope);

        $node = new ContentNode();
        $node->setParentNode($parentNode);
        $node->setParentScopeUsed(true);

        $actualScopes = $node->getScopesConsideringParent();
        self::assertCount(1, $actualScopes);
        self::assertContains($parentNodeScope, $actualScopes);
    }

    /**
     * @dataProvider getAccessByPropertyAccessorDataProvider
     */
    public function testAccessByPropertyAccessor(string $propertyPath): void
    {
        self::assertTrue($this->propertyAccessor->isReadable(new ContentNode(), $propertyPath));
    }

    public function getAccessByPropertyAccessorDataProvider(): array
    {
        return [
            ['referencedMenuItems'],
            ['referencedConsents']
        ];
    }
}

<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\ContentNodeUtils;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolver;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ScopeMatcher;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Component\Testing\Unit\EntityTrait;

class ContentNodeTreeResolverTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var ScopeMatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeMatcher;

    /**
     * @var ContentNodeTreeResolver
     */
    private $resolver;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeMatcher = $this->getMockBuilder(ScopeMatcher::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resolver = new ContentNodeTreeResolver(
            $this->doctrineHelper,
            $this->scopeMatcher
        );
    }

    public function testShouldSupportContentNodeAndScopeAsArgs()
    {
        $node = new ContentNode();
        $scope = new Scope();
        $this->assertTrue($this->resolver->supports($node, $scope));
    }

    public function testShouldReturnNullIfCantMatchScope()
    {
        $node = new ContentNode();
        $scope = new Scope();

        $this->scopeMatcher->expects($this->any())
            ->method('getMatchingScopePriority')
            ->willReturn(false);

        $this->assertNull($this->resolver->getResolvedContentNode($node, $scope));
    }

    public function testShouldReturnNullIfContentVariantIsNotResolved()
    {
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => 2]);
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => 5]);
        $localization = $this->getEntity(Localization::class, ['id' => 42, 'name' => 'test_localization']);

        $slugUrl = '/node';
        $slug = new Slug();
        $slug->setUrl($slugUrl);
        $slug->setLocalization($localization);
        $defaultVariant = new ContentVariant();
        $defaultVariant->setDefault(true)
            ->addSlug($slug);
        $node->addContentVariant($defaultVariant);

        $this->scopeMatcher->expects($this->any())
            ->method('getMatchingScopePriority')
            ->willReturn(12345);
        $this->scopeMatcher->expects($this->any())
            ->method('getBestMatchByScope')
            ->willReturn(false);

        $this->assertNull($this->resolver->getResolvedContentNode($node, $scope));
    }

    public function testShouldReturnResolvedNodeIfAllConditionsSatisfied()
    {
        $localization = $this->getEntity(Localization::class, ['id' => 42, 'name' => 'test_localization']);
        $resolvedNode = new ResolvedContentNode(
            2,
            'root__node',
            new ArrayCollection([
                (new LocalizedFallbackValue())
                    ->setString('some-title')
                    ->setLocalization($localization)
            ]),
            (new ResolvedContentVariant())
                ->setData(['id' => 2, 'type' => 'test_type', 'associations' => ['anything']])
                ->addLocalizedUrl((new LocalizedFallbackValue())->setString('/node')->setLocalization($localization))
        );
        $resolvedNode->addChildNode(
            new ResolvedContentNode(
                3,
                'root__node__child',
                new ArrayCollection([]),
                (new ResolvedContentVariant())
                    ->setData(['id' => 3, 'type' => 'test_type'])
                    ->addLocalizedUrl(
                        (new LocalizedFallbackValue())->setString('/node/child')->setLocalization($localization)
                    )
            )
        );

        /** @var ContentNode $childNode */
        $childNode = $this->getEntity(ContentNode::class, ['id' => 3]);
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => 5]);
        $childSlugUrl = '/node/child';
        $childSlug = new Slug();
        $childSlug->setUrl($childSlugUrl);
        $childSlug->setLocalization($localization);
        /** @var ContentVariant $defaultChildVariant */
        $defaultChildVariant = $this->getEntity(ContentVariant::class, ['id' => 3, 'type' => 'test_type']);
        $defaultChildVariant->setDefault(true)
            ->addSlug($childSlug);
        $childNode->addContentVariant($defaultChildVariant);

        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => 2]);
        $slugUrl = '/node';
        $slug = new Slug();
        $slug->setUrl($slugUrl);
        $slug->setLocalization($localization);
        /** @var ContentVariant $defaultVariant */
        $defaultVariant = $this->getEntity(ContentVariant::class, ['id' => 2, 'type' => 'test_type']);
        $defaultVariant
            ->setDefault(true)
            ->addSlug($slug);
        $node->addContentVariant($defaultVariant);
        $node->addChildNode($childNode);
        $node->addTitle((new LocalizedFallbackValue())->setString('some-title')->setLocalization($localization));
        $childNode->setParentNode($node);

        $this->scopeMatcher->expects($this->any())
            ->method('getMatchingScopePriority')
            ->willReturn(12345);
        $this->scopeMatcher->expects($this->any())
            ->method('getBestMatchByScope')
            ->willReturn($defaultVariant);

        /** @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject $metadata */
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())->method('getFieldNames')->willReturn(['id', 'type']);
        $metadata->expects($this->once())->method('getAssociationNames')->willReturn(['slugs', 'associations']);
        $metadata->expects($this->any())->method('getFieldValue')
            ->willReturnOnConsecutiveCalls(2, 'test_type', (new ArrayCollection([$slug])), ['anything']);

        /** @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject $childMetadata */
        $childMetadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $childMetadata->expects($this->once())->method('getFieldNames')->willReturn(['id', 'type']);
        $childMetadata->expects($this->once())->method('getAssociationNames')->willReturn(['slugs', 'scopes']);
        $childMetadata->expects($this->any())->method('getFieldValue')
            ->willReturnOnConsecutiveCalls(
                3,
                'test_type',
                new ArrayCollection([$childSlug]),
                new ArrayCollection([$scope])
            );

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityMetadata')
            ->willReturnOnConsecutiveCalls($metadata, $childMetadata);

        $this->assertEquals($resolvedNode, $this->resolver->getResolvedContentNode($node, $scope));
    }
}

<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Cache\Dumper;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeResolver as CacheContentNodeTreeResolver;
use Oro\Bundle\WebCatalogBundle\Cache\Dumper\ContentNodeTreeDumper;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolver;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\Testing\Unit\EntityTrait;

class ContentNodeTreeDumperTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ContentNodeTreeResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    private $nodeTreeResolver;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var Cache|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cache;

    /**
     * @var ContentNodeTreeDumper
     */
    private $dumper;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cache = $this->getMock(Cache::class);
        $this->nodeTreeResolver = $this->getMock(ContentNodeTreeResolverInterface::class);

        $this->dumper = new ContentNodeTreeDumper(
            $this->nodeTreeResolver,
            $this->doctrineHelper,
            $this->cache
        );
    }

    public function testShouldSaveEmptyCacheIfNodeNotResolved()
    {
        $node = new ContentNode();
        $scope = new Scope();

        $this->nodeTreeResolver->expects($this->any())
            ->method('getResolvedContentNode')
            ->with($node, $scope)
            ->willReturn(null);

        $this->cache->expects($this->once())
            ->method('save')
            ->with(
                CacheContentNodeTreeResolver::getCacheKey($node, $scope),
                []
            );

        $this->dumper->dump($node, $scope);
    }

    public function testShouldSaveCacheIfNodeResolved()
    {
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => 2]);
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => 5]);
        $resolvedNode = new ResolvedContentNode(
            1,
            'root',
            new ArrayCollection(
                [
                    (new LocalizedFallbackValue())->setString('Title 1'),
                    (new LocalizedFallbackValue())
                        ->setString('Title 1 EN')
                        ->setFallback(FallbackType::PARENT_LOCALIZATION)
                        ->setLocalization($this->getEntity(Localization::class, ['id' => 5])),
                ]
            ),
            (new ResolvedContentVariant())->setData(['id' => 3, 'type' => 'test_type', 'test' => 1])
                ->addLocalizedUrl((new LocalizedFallbackValue())->setString('/test'))
        );
        $resolvedNode->addChildNode(
            new ResolvedContentNode(
                2,
                'root__second',
                new ArrayCollection([(new LocalizedFallbackValue())->setString('Child Title 1')]),
                (new ResolvedContentVariant())->addLocalizedUrl((new LocalizedFallbackValue())->setString('/test/c'))
                    ->setData([
                        'id' => 7,
                        'type' => 'test_type',
                        'skipped_null' => null,
                        'sub_array' => ['a' => 'b'],
                        'sub_iterator' => new ArrayCollection(
                            ['c' => $this->getEntity(Localization::class, ['id' => 3])]
                        )
                    ])
            )
        );
        $convertedNode = [
            'id' => $resolvedNode->getId(),
            'identifier' => $resolvedNode->getIdentifier(),
            'titles' => [
                ['string' => 'Title 1', 'localization' => null, 'fallback' => null],
                [
                    'string' => 'Title 1 EN',
                    'localization' => ['entity_class' => Localization::class, 'entity_id' => 5],
                    'fallback' => 'parent_localization',
                ],
            ],
            'contentVariant' => [
                'data' => ['id' => 3, 'type' => 'test_type', 'test' => 1],
                'localizedUrls' => [['string' => '/test', 'localization' => null, 'fallback' => null]]
            ],
            'childNodes' => [
                [
                    'id' => 2,
                    'identifier' => 'root__second',
                    'titles' => [['string' => 'Child Title 1', 'localization' => null, 'fallback' => null]],
                    'contentVariant' => [
                        'data' => [
                            'id' => 7,
                            'type' => 'test_type',
                            'sub_array' => ['a' => 'b'],
                            'sub_iterator' => ['c' => ['entity_class' => Localization::class, 'entity_id' => 3]]
                        ],
                        'localizedUrls' => [['string' => '/test/c', 'localization' => null, 'fallback' => null]]
                    ],
                    'childNodes' => [],
                ],
            ],
        ];

        $this->doctrineHelper->expects($this->any())
            ->method('isManageableEntity')
            ->willReturn(true);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturnCallback(
                function ($object) {
                    return get_class($object);
                }
            );
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->willReturnCallback(
                function ($object) {
                    return $object->getId();
                }
            );
        $this->nodeTreeResolver->expects($this->any())
            ->method('getResolvedContentNode')
            ->with($node, $scope)
            ->willReturn($resolvedNode);
        $this->cache->expects($this->once())
            ->method('save')
            ->with(CacheContentNodeTreeResolver::getCacheKey($node, $scope), $convertedNode);

        $this->dumper->dump($node, $scope);
    }
}

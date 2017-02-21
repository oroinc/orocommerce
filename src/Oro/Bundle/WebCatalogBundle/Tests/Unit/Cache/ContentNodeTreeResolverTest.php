<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeResolver;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\Testing\Unit\EntityTrait;

class ContentNodeTreeResolverTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var Cache|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cache;

    /**
     * @var ContentNodeTreeResolver
     */
    private $resolver;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cache = $this->createMock(Cache::class);

        $this->resolver = new ContentNodeTreeResolver($this->doctrineHelper, $this->cache);
    }

    public function testGetCacheKey()
    {
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => 2]);
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => 5]);

        $this->assertEquals('node_2_scope_5', ContentNodeTreeResolver::getCacheKey($node, $scope));
    }

    public function testSupports()
    {
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => 2]);
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => 5]);

        $this->cache->expects($this->once())
            ->method('contains')
            ->with('node_2_scope_5')
            ->willReturn(true);

        $this->assertTrue($this->resolver->supports($node, $scope));
    }

    public function testGetResolvedContentNodeWhenCacheIsEmpty()
    {
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => 2]);
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => 5]);

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('node_2_scope_5')
            ->willReturn([]);

        $this->assertNull($this->resolver->getResolvedContentNode($node, $scope));
    }

    public function testGetResolvedContentNode()
    {
        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => 2]);
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => 5]);

        $cacheData = [
            'id' => 1,
            'identifier' => 'root',
            'resolveVariantTitle' => true,
            'titles' => [
                ['string' => 'Title 1', 'localization' => null, 'fallback' => FallbackType::NONE],
                [
                    'string' => 'Title 1 EN',
                    'localization' => ['entity_class' => Localization::class, 'entity_id' => 5],
                    'fallback' => FallbackType::PARENT_LOCALIZATION
                ]
            ],
            'contentVariant' => [
                'data' => ['id' => 3, 'type' => 'test_type', 'test' => 1],
                'localizedUrls' => [
                    ['string' => '/test', 'localization' => null, 'fallback' => FallbackType::NONE]
                ]
            ],
            'childNodes' => [
                [
                    'id' => 2,
                    'identifier' => 'root__second',
                    'resolveVariantTitle' => false,
                    'titles' => [
                        ['string' => 'Child Title 1', 'localization' => null, 'fallback' => FallbackType::NONE]
                    ],
                    'contentVariant' => [
                        'data' => ['id' => 7, 'type' => 'test_type', 'test' => 2],
                        'localizedUrls' => [
                            ['string' => '/test/content', 'localization' => null, 'fallback' => FallbackType::NONE]
                        ]
                    ],
                    'childNodes' => []
                ]
            ]
        ];
        $expected = new ResolvedContentNode(
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
            (new ResolvedContentVariant())
                ->setData(['id' => 3, 'type' => 'test_type', 'test' => 1])
                ->addLocalizedUrl((new LocalizedFallbackValue())->setString('/test')),
            true
        );
        $expected->addChildNode(
            new ResolvedContentNode(
                2,
                'root__second',
                new ArrayCollection(
                    [
                        (new LocalizedFallbackValue())->setString('Child Title 1')
                    ]
                ),
                (new ResolvedContentVariant())
                    ->setData(['id' => 7, 'type' => 'test_type', 'test' => 2])
                    ->addLocalizedUrl((new LocalizedFallbackValue())->setString('/test/content')),
                false
            )
        );

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('node_2_scope_5')
            ->willReturn($cacheData);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityReference')
            ->willReturnCallback(
                function ($className, $id) {
                    return $this->getEntity($className, ['id' => $id]);
                }
            );

        $this->assertEquals($expected, $this->resolver->getResolvedContentNode($node, $scope));
    }
}

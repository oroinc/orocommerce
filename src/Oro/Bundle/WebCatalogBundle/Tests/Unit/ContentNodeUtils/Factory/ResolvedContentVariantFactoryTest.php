<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\ContentNodeUtils\Factory;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Tests\Unit\Stub\LocalizationStub;
use Oro\Bundle\PlatformBundle\Tests\Unit\Stub\ProxyStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Factory\ResolvedContentVariantFactory;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;

class ResolvedContentVariantFactoryTest extends \PHPUnit\Framework\TestCase
{
    private ResolvedContentVariantFactory $factory;

    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);

        $this->factory = new ResolvedContentVariantFactory($managerRegistry);

        $entityManager = $this->createMock(EntityManager::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->with(ContentVariant::class)
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::any())
            ->method('getReference')
            ->willReturnCallback(
                static fn ($class, $id) => $class === Localization::class
                    ? new LocalizationStub($id)
                    : new ProxyStub($class, $id)
            );

        $classMetadata = new ClassMetadata(ContentVariant::class);
        $classMetadata->fieldMappings = ['id' => [], 'default' => []];
        $classMetadata->associationMappings = [
            'node' => [
                'targetEntity' => ContentNode::class,
                'type' => ClassMetadataInfo::TO_ONE,
            ],
            'product' => ['targetEntity' => Product::class, 'type' => ClassMetadataInfo::TO_ONE],
            'slugs' => ['targetEntity' => Slug::class, 'type' => ClassMetadataInfo::TO_MANY],
        ];

        $entityManager
            ->expects(self::any())
            ->method('getClassMetadata')
            ->with(ContentVariant::class)
            ->willReturn($classMetadata);
    }

    /**
     * @dataProvider createFromArrayDataProvider
     */
    public function testCreateFromArray(array $data, ResolvedContentVariant $expected): void
    {
        self::assertEquals($expected, $this->factory->createFromArray($data));
    }

    public function createFromArrayDataProvider(): array
    {
        return [
            'empty' => ['data' => [], 'expected' => new ResolvedContentVariant()],
            'with scalar fields' => [
                'data' => ['id' => 42, 'default' => true],
                'expected' => (new ResolvedContentVariant())->setData(['id' => 42, 'default' => true]),
            ],
            'with slug' => [
                'data' => [
                    'id' => 42,
                    'default' => true,
                    'slugs' => [
                        [
                            'url' => '/sample/url',
                        ],
                    ],
                ],
                'expected' => (new ResolvedContentVariant())
                    ->setData(['id' => 42, 'default' => true])
                    ->addLocalizedUrl((new LocalizedFallbackValue())->setString('/sample/url')),
            ],
            'with multiple slugs' => [
                'data' => [
                    'id' => 42,
                    'default' => true,
                    'slugs' => [
                        [
                            'url' => '/sample/url',
                        ],
                        [
                            'url' => '/sample/url/en',
                            'localization' => ['id' => 100],
                        ],
                    ],
                ],
                'expected' => (new ResolvedContentVariant())
                    ->setData(['id' => 42, 'default' => true])
                    ->addLocalizedUrl((new LocalizedFallbackValue())->setString('/sample/url'))
                    ->addLocalizedUrl(
                        (new LocalizedFallbackValue())
                            ->setString('/sample/url/en')
                            ->setLocalization(new LocalizationStub(100))
                    ),
            ],
            'with to-one association' => [
                'data' => [
                    'id' => 42,
                    'default' => true,
                    'slugs' => [
                        [
                            'url' => '/sample/url',
                        ],
                        [
                            'url' => '/sample/url/en',
                            'localization' => ['id' => 100],
                        ],
                    ],
                    'product' => ['id' => 1000],
                ],
                'expected' => (new ResolvedContentVariant())
                    ->setData(['id' => 42, 'default' => true, 'product' => new ProxyStub(Product::class, 1000)])
                    ->addLocalizedUrl((new LocalizedFallbackValue())->setString('/sample/url'))
                    ->addLocalizedUrl(
                        (new LocalizedFallbackValue())
                            ->setString('/sample/url/en')
                            ->setLocalization(new LocalizationStub(100))
                    ),
            ],
        ];
    }
}

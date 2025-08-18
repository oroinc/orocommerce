<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\ContentNodeUtils\Factory;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Cache\Normalizer\LocalizedFallbackValueNormalizer;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
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

    #[\Override]
    protected function setUp(): void
    {
        $classMetadata = new ClassMetadata(ContentVariant::class);
        $classMetadata->fieldMappings = ['id' => [], 'default' => []];
        $classMetadata->associationMappings = [
            'node' => ['targetEntity' => ContentNode::class, 'type' => ClassMetadataInfo::TO_ONE],
            'product' => ['targetEntity' => Product::class, 'type' => ClassMetadataInfo::TO_ONE],
            'slugs' => ['targetEntity' => Slug::class, 'type' => ClassMetadataInfo::TO_MANY]
        ];

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects(self::any())
            ->method('getReference')
            ->willReturnCallback(function (string $class, int $id) {
                return new ProxyStub($class, $id);
            });
        $entityManager->expects(self::any())
            ->method('getClassMetadata')
            ->with(ContentVariant::class)
            ->willReturn($classMetadata);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(ContentVariant::class)
            ->willReturn($entityManager);

        $localizedFallbackValueNormalizer = $this->createMock(LocalizedFallbackValueNormalizer::class);
        $localizedFallbackValueNormalizer->expects(self::any())
            ->method('denormalize')
            ->willReturnCallback(function (array $value, string $entityClass) {
                self::assertEquals(LocalizedFallbackValue::class, $entityClass);

                return (new LocalizedFallbackValue())->setString($value['string']);
            });

        $this->factory = new ResolvedContentVariantFactory($doctrine, $localizedFallbackValueNormalizer);
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
                'expected' => (new ResolvedContentVariant())->setData(['id' => 42, 'default' => true])
            ],
            'with slug' => [
                'data' => [
                    'id' => 42,
                    'default' => true,
                    'slugs' => [['url' => '/sample/url']]
                ],
                'expected' => (new ResolvedContentVariant())
                    ->setData(['id' => 42, 'default' => true])
                    ->addLocalizedUrl((new LocalizedFallbackValue())->setString('/sample/url'))
            ],
            'with to-one association' => [
                'data' => [
                    'id' => 42,
                    'default' => true,
                    'slugs' => [['url' => '/sample/url']],
                    'product' => ['id' => 1000]
                ],
                'expected' => (new ResolvedContentVariant())
                    ->setData(['id' => 42, 'default' => true, 'product' => new ProxyStub(Product::class, 1000)])
                    ->addLocalizedUrl((new LocalizedFallbackValue())->setString('/sample/url'))
            ]
        ];
    }
}

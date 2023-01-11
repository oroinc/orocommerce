<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\ContentNodeUtils\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Cache\Normalizer\LocalizedFallbackValueCollectionNormalizer;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Factory\ResolvedContentNodeFactory;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Factory\ResolvedContentNodeIdentifierGenerator;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Factory\ResolvedContentVariantFactory;
use Oro\Bundle\WebCatalogBundle\Exception\InvalidArgumentException;

class ResolvedContentNodeFactoryTest extends \PHPUnit\Framework\TestCase
{
    private ResolvedContentNodeFactory $factory;

    protected function setUp(): void
    {
        $resolvedNodeIdentifierGenerator = $this->createMock(ResolvedContentNodeIdentifierGenerator::class);
        $resolvedContentVariantFactory = $this->createMock(ResolvedContentVariantFactory::class);
        $localizedFallbackValueCollectionNormalizer = $this->createMock(
            LocalizedFallbackValueCollectionNormalizer::class
        );

        $this->factory = new ResolvedContentNodeFactory(
            $resolvedNodeIdentifierGenerator,
            $resolvedContentVariantFactory,
            $localizedFallbackValueCollectionNormalizer
        );

        $resolvedNodeIdentifierGenerator
            ->expects(self::any())
            ->method('getIdentifierByUrl')
            ->willReturnCallback(static fn ($url) => 'identifier__' . $url);

        $resolvedContentVariantFactory
            ->expects(self::any())
            ->method('createFromArray')
            ->willReturnCallback(static fn ($data) => (new ResolvedContentVariant())->setData($data));

        $localizedFallbackValueCollectionNormalizer
            ->expects(self::any())
            ->method('denormalize')
            ->willReturnCallback(function (array $values, string $entityClass) {
                self::assertEquals(LocalizedFallbackValue::class, $entityClass);

                return new ArrayCollection(
                    array_map(
                        static fn ($value) => (new LocalizedFallbackValue())->setString($value['string']),
                        $values
                    )
                );
            });
    }

    public function testWhenEmptyArray(): void
    {
        $this->expectExceptionObject(
            new InvalidArgumentException('Element "id" is required and expected to be of type int')
        );

        $this->factory->createFromArray([]);
    }

    /**
     * @dataProvider invalidContentVariantDataProvider
     */
    public function testWhenConventVariantIsInvalid(array $data): void
    {
        $this->expectExceptionObject(
            new InvalidArgumentException(
                sprintf(
                    'Element "contentVariant" is required and expected to be array or %s',
                    ResolvedContentNode::class
                )
            )
        );

        $this->factory->createFromArray($data);
    }

    public function invalidContentVariantDataProvider(): array
    {
        return [
            [['id' => 42]],
            [['id' => 42, 'contentVariant' => null]],
            [['id' => 42, 'contentVariant' => new \stdClass()]],
        ];
    }

    /**
     * @dataProvider noIdentifierAndLocalizedUrlsDataProvider
     */
    public function testWhenNoIdentifierAndLocalizedUrls(array $data, \Exception $exception): void
    {
        $this->expectExceptionObject($exception);

        $this->factory->createFromArray($data);
    }

    public function noIdentifierAndLocalizedUrlsDataProvider(): array
    {
        return [
            [
                ['id' => 42, 'contentVariant' => $this->createMock(ResolvedContentVariant::class)],
                'exception' => new InvalidArgumentException(
                    'Either "identifier" or "localizedUrls" element is expected to be present'
                ),
            ],
            [
                [
                    'id' => 42,
                    'contentVariant' => $this->createMock(ResolvedContentVariant::class),
                    'localizedUrls' => null,
                ],
                'exception' => new InvalidArgumentException(
                    'Either "identifier" or "localizedUrls" element is expected to be present'
                ),
            ],
            [
                [
                    'id' => 42,
                    'contentVariant' => $this->createMock(ResolvedContentVariant::class),
                    'localizedUrls' => new \stdClass(),
                ],
                'exception' => new InvalidArgumentException('Element "localizedUrls" is expected to be array'),
            ],
        ];
    }


    /**
     * @dataProvider createFromArrayDataProvider
     */
    public function testCreateFromArray(array $data, ResolvedContentNode $expected): void
    {
        self::assertEquals($expected, $this->factory->createFromArray($data));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function createFromArrayDataProvider(): array
    {
        return [
            'no priority, rewriteVariantTitle, titles' => [
                'data' => [
                    'id' => 42,
                    'identifier' => 'root__sample',
                    'contentVariant' => new ResolvedContentVariant(),
                ],
                'expected' => new ResolvedContentNode(
                    42,
                    'root__sample',
                    0,
                    new ArrayCollection(),
                    new ResolvedContentVariant(),
                    true
                ),
            ],
            'with left' => [
                'data' => [
                    'id' => 42,
                    'left' => 3,
                    'identifier' => 'root__sample',
                    'contentVariant' => new ResolvedContentVariant(),
                ],
                'expected' => new ResolvedContentNode(
                    42,
                    'root__sample',
                    3,
                    new ArrayCollection(),
                    new ResolvedContentVariant(),
                    true
                ),
            ],
            'with priority' => [
                'data' => [
                    'id' => 42,
                    'identifier' => 'root__sample',
                    'priority' => 4,
                    'contentVariant' => new ResolvedContentVariant(),
                ],
                'expected' => new ResolvedContentNode(
                    42,
                    'root__sample',
                    4,
                    new ArrayCollection(),
                    new ResolvedContentVariant(),
                    true
                ),
            ],
            'with rewriteVariantTitle' => [
                'data' => [
                    'id' => 42,
                    'identifier' => 'root__sample',
                    'priority' => 4,
                    'contentVariant' => new ResolvedContentVariant(),
                    'rewriteVariantTitle' => false,
                ],
                'expected' => new ResolvedContentNode(
                    42,
                    'root__sample',
                    4,
                    new ArrayCollection(),
                    new ResolvedContentVariant(),
                    false
                ),
            ],
            'with titles' => [
                'data' => [
                    'id' => 42,
                    'identifier' => 'root__sample',
                    'priority' => 4,
                    'titles' => [['string' => 'Sample Title']],
                    'contentVariant' => new ResolvedContentVariant(),
                    'rewriteVariantTitle' => false,
                ],
                'expected' => new ResolvedContentNode(
                    42,
                    'root__sample',
                    4,
                    new ArrayCollection([(new LocalizedFallbackValue())->setString('Sample Title')]),
                    new ResolvedContentVariant(),
                    false
                ),
            ],
            'with localizedUrls' => [
                'data' => [
                    'id' => 42,
                    'localizedUrls' => [
                        ['text' => 'sample', 'localization' => ['id' => 100]],
                        ['text' => 'sample'],
                    ],
                    'priority' => 4,
                    'titles' => [['string' => 'Sample Title']],
                    'contentVariant' => new ResolvedContentVariant(),
                    'rewriteVariantTitle' => false,
                ],
                'expected' => new ResolvedContentNode(
                    42,
                    'identifier__sample',
                    4,
                    new ArrayCollection([(new LocalizedFallbackValue())->setString('Sample Title')]),
                    new ResolvedContentVariant(),
                    false
                ),
            ],
            'with contentVariant data' => [
                'data' => [
                    'id' => 42,
                    'localizedUrls' => [
                        ['text' => 'sample', 'localization' => ['id' => 100]],
                        ['text' => 'sample'],
                    ],
                    'priority' => 4,
                    'titles' => [['string' => 'Sample Title']],
                    'contentVariant' => ['id' => 1000],
                    'rewriteVariantTitle' => false,
                ],
                'expected' => new ResolvedContentNode(
                    42,
                    'identifier__sample',
                    4,
                    new ArrayCollection([(new LocalizedFallbackValue())->setString('Sample Title')]),
                    (new ResolvedContentVariant())->setData(['id' => 1000]),
                    false
                ),
            ],
        ];
    }
}

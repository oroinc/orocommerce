<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener\ORM;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\CatalogBundle\EventListener\ORM\ProductMetadataBuilder;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductMetadataBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductMetadataBuilder */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new ProductMetadataBuilder();
    }

    /**
     * @dataProvider supportsDataProvider
     *
     * @param ConfigInterface $config
     * @param bool $expected
     */
    public function testSupports(ConfigInterface $config, $expected)
    {
        $this->assertEquals($expected, $this->builder->supports($config));
    }

    public function supportsDataProvider(): array
    {
        return [
            'unsupported config id' => [
                'config' => new Config(new FieldConfigId('test', Product::class, 'field1')),
                'expected' => false
            ],
            'unsupported class name' => [
                'config' => new Config(new EntityConfigId('test', \stdClass::class)),
                'expected' => false
            ],
            'supported' => [
                'config' => new Config(new EntityConfigId('test', Product::class)),
                'expected' => true
            ],
        ];
    }

    public function testBuildWithoutCategoryField()
    {
        $metadata = new ClassMetadataInfo('product');
        $metadataBuilder = new ClassMetadataBuilder($metadata);

        $this->builder->build($metadataBuilder, new Config(new EntityConfigId('test', Product::class)));

        $this->assertEmpty($metadata->associationMappings);
    }

    public function testBuild()
    {
        $metadata = new ClassMetadataInfo('product');
        $metadata->associationMappings['category']['cascade'] = ['persist', 'remove', 'detach'];
        $metadata->associationMappings['category']['isCascadeDetach'] = true;

        $metadataBuilder = new ClassMetadataBuilder($metadata);

        $this->builder->build($metadataBuilder, new Config(new EntityConfigId('test', Product::class)));

        $this->assertEquals(
            [
                'category' => [
                    'cascade' => ['persist', 'remove'],
                    'isCascadeDetach' => false
                ]
            ],
            $metadata->associationMappings
        );
    }
}

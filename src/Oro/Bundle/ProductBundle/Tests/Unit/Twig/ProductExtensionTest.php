<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Twig;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Expression\Autocomplete\AutocompleteFieldsProvider;
use Oro\Bundle\ProductBundle\RelatedItem\FinderStrategyInterface;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct\FinderDatabaseStrategy;
use Oro\Bundle\ProductBundle\Twig\ProductExtension;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ProductExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait, EntityTrait;

    /** @var AutocompleteFieldsProvider */
    protected $autocompleteFieldsProvider;

    /** @var ProductExtension */
    protected $extension;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var FinderDatabaseStrategy|\PHPUnit\Framework\MockObject\MockObject */
    protected $finderDatabaseStrategy;

    protected function setUp(): void
    {
        $this->autocompleteFieldsProvider = $this->getMockBuilder(AutocompleteFieldsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->finderDatabaseStrategy = $this->getMockBuilder(FinderStrategyInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_product.autocomplete_fields_provider', $this->autocompleteFieldsProvider)
            ->add('oro_entity.doctrine_helper', $this->doctrineHelper)
            ->add('oro_product.related_item.related_product.finder_strategy', $this->finderDatabaseStrategy)
            ->getContainer($this);

        $this->extension = new ProductExtension($container);
    }

    public function testGetName(): void
    {
        $this->assertEquals(ProductExtension::NAME, $this->extension->getName());
    }

    public function testIsConfigurableSimple(): void
    {
        $this->assertFalse(
            $this->callTwigFunction($this->extension, 'is_configurable_product_type', [Product::TYPE_SIMPLE])
        );
    }

    public function testIsConfigurable(): void
    {
        $this->assertTrue(
            $this->callTwigFunction($this->extension, 'is_configurable_product_type', [Product::TYPE_CONFIGURABLE])
        );
    }

    /**
     * @dataProvider dataProviderRelatedProductIds
     */
    public function testGetRelatedProductsIds(array $ids): void
    {
        $this->finderDatabaseStrategy->expects($this->once())
            ->method('findIds')
            ->willReturn($ids);

        $this->assertSame($ids, $this->extension->getRelatedProductsIds(new Product()));
    }

    public function dataProviderRelatedProductIds(): array
    {
        return [
            [[2, 3, 4]],
            [[]]
        ];
    }
}

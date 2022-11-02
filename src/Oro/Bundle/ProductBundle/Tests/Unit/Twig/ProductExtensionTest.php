<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Twig;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Expression\Autocomplete\AutocompleteFieldsProvider;
use Oro\Bundle\ProductBundle\RelatedItem\FinderStrategyInterface;
use Oro\Bundle\ProductBundle\RelatedItem\Helper\RelatedItemConfigHelper;
use Oro\Bundle\ProductBundle\Twig\ProductExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ProductExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var AutocompleteFieldsProvider */
    private $autocompleteFieldsProvider;

    /** @var FinderStrategyInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $relatedProductFinderStrategy;

    /** @var FinderStrategyInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $upsellProductFinderStrategy;

    /** @var RelatedItemConfigHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $relatedItemConfigHelper;

    /** @var ProductExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->autocompleteFieldsProvider = $this->createMock(AutocompleteFieldsProvider::class);
        $this->relatedProductFinderStrategy = $this->createMock(FinderStrategyInterface::class);
        $this->upsellProductFinderStrategy = $this->createMock(FinderStrategyInterface::class);
        $this->relatedItemConfigHelper = $this->createMock(RelatedItemConfigHelper::class);

        $container = self::getContainerBuilder()
            ->add('oro_product.autocomplete_fields_provider', $this->autocompleteFieldsProvider)
            ->add('oro_product.related_item.related_product.finder_strategy', $this->relatedProductFinderStrategy)
            ->add('oro_product.related_item.upsell_product.finder_strategy', $this->upsellProductFinderStrategy)
            ->add('oro_product.related_item.helper.config_helper', $this->relatedItemConfigHelper)
            ->getContainer($this);

        $this->extension = new ProductExtension($container);
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

    public function testGetUpsellProductsIds(): void
    {
        $ids = [2, 3, 4];

        $this->upsellProductFinderStrategy->expects($this->once())
            ->method('findIds')
            ->willReturn($ids);

        $this->assertSame(
            $ids,
            $this->callTwigFunction($this->extension, 'get_upsell_products_ids', [new Product()])
        );
    }

    public function testGetRelatedProductsIds(): void
    {
        $ids = [2, 3, 4];

        $this->relatedProductFinderStrategy->expects($this->once())
            ->method('findIds')
            ->willReturn($ids);

        $this->assertSame(
            $ids,
            $this->callTwigFunction($this->extension, 'get_related_products_ids', [new Product()])
        );
    }

    public function testGetRelatedItemsTranslationKeyReturnsTranslationKey()
    {
        $translationKey = 'translation_key';

        $this->relatedItemConfigHelper->expects($this->once())
            ->method('getRelatedItemsTranslationKey')
            ->willReturn($translationKey);

        $this->assertEquals(
            $translationKey,
            $this->callTwigFunction($this->extension, 'get_related_items_translation_key', [])
        );
    }
}

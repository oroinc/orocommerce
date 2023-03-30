<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Twig;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Expression\Autocomplete\AutocompleteFieldsProvider;
use Oro\Bundle\ProductBundle\RelatedItem\FinderStrategyInterface;
use Oro\Bundle\ProductBundle\RelatedItem\Helper\RelatedItemConfigHelper;
use Oro\Bundle\ProductBundle\Twig\ProductExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private MockObject|AutocompleteFieldsProvider $autocompleteFieldsProvider;

    private FinderStrategyInterface|MockObject $relatedProductFinderStrategy;

    private FinderStrategyInterface|MockObject $upsellProductFinderStrategy;

    private RelatedItemConfigHelper|MockObject $relatedItemConfigHelper;

    private ProductExtension $extension;

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

    public function testGetAutocompleteData(): void
    {
        $numericalOnly = true;
        $withRelations = false;
        $data = ['key' => 'value'];

        $this->autocompleteFieldsProvider->expects(self::once())
            ->method('getAutocompleteData')
            ->with($numericalOnly, $withRelations)
            ->willReturn($data);

        self::assertEquals(
            $data,
            self::callTwigFunction(
                $this->extension,
                'oro_product_expression_autocomplete_data',
                [$numericalOnly, $withRelations]
            )
        );
    }

    public function testGetAutocompleteDataWithDefaultArguments(): void
    {
        $data = ['key' => 'value'];

        $this->autocompleteFieldsProvider->expects(self::once())
            ->method('getAutocompleteData')
            ->with(self::isFalse(), self::isTrue())
            ->willReturn($data);

        self::assertEquals(
            $data,
            self::callTwigFunction($this->extension, 'oro_product_expression_autocomplete_data', [])
        );
    }

    public function testIsConfigurableSimple(): void
    {
        self::assertFalse(
            self::callTwigFunction($this->extension, 'is_configurable_product_type', [Product::TYPE_SIMPLE])
        );
    }

    public function testIsConfigurable(): void
    {
        self::assertTrue(
            self::callTwigFunction($this->extension, 'is_configurable_product_type', [Product::TYPE_CONFIGURABLE])
        );
    }

    public function testIsKitWhenSimple(): void
    {
        self::assertFalse(
            self::callTwigFunction($this->extension, 'is_kit_product_type', [Product::TYPE_SIMPLE])
        );
    }

    public function testIsKitWhenKit(): void
    {
        self::assertTrue(
            self::callTwigFunction($this->extension, 'is_kit_product_type', [Product::TYPE_KIT])
        );
    }

    public function testGetUpsellProductsIds(): void
    {
        $ids = [2, 3, 4];

        $this->upsellProductFinderStrategy->expects(self::once())
            ->method('findIds')
            ->willReturn($ids);

        self::assertSame(
            $ids,
            self::callTwigFunction($this->extension, 'get_upsell_products_ids', [new Product()])
        );
    }

    public function testGetRelatedProductsIds(): void
    {
        $ids = [2, 3, 4];

        $this->relatedProductFinderStrategy->expects(self::once())
            ->method('findIds')
            ->willReturn($ids);

        self::assertSame(
            $ids,
            self::callTwigFunction($this->extension, 'get_related_products_ids', [new Product()])
        );
    }

    public function testGetRelatedItemsTranslationKeyReturnsTranslationKey(): void
    {
        $translationKey = 'translation_key';

        $this->relatedItemConfigHelper->expects(self::once())
            ->method('getRelatedItemsTranslationKey')
            ->willReturn($translationKey);

        self::assertEquals(
            $translationKey,
            self::callTwigFunction($this->extension, 'get_related_items_translation_key', [])
        );
    }
}

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
use Symfony\Component\Form\FormView;

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

    protected function setUp()
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

    public function testGetName()
    {
        $this->assertEquals(ProductExtension::NAME, $this->extension->getName());
    }

    public function testIsConfigurableSimple()
    {
        $this->assertFalse(
            self::callTwigFunction($this->extension, 'is_configurable_product_type', [Product::TYPE_SIMPLE])
        );
    }

    public function testIsConfigurable()
    {
        $this->assertTrue(
            self::callTwigFunction($this->extension, 'is_configurable_product_type', [Product::TYPE_CONFIGURABLE])
        );
    }

    /**
     * @param array $ids
     * @dataProvider dataProviderRelatedProductIds
     */
    public function testGetRelatedProductsIds(array $ids)
    {
        $this->finderDatabaseStrategy->expects($this->once())
            ->method('findIds')
            ->willReturn($ids);

        $this->assertSame($ids, $this->extension->getRelatedProductsIds(new Product()));
    }

    /**
     * @return array
     */
    public function dataProviderRelatedProductIds()
    {
        return [
            [[2, 3, 4]],
            [[]]
        ];
    }

    /**
     * @dataProvider dataSetUniqueLineItemFormId
     * @param FormView $formView
     * @param Product $product
     * @param string $expectedId
     */
    public function testSetUniqueLineItemFormId($formView, $product, $expectedId)
    {
        self::callTwigFunction($this->extension, 'set_unique_line_item_form_id', [$formView, $product]);
        $this->assertEquals($expectedId, $formView->vars['id']);
        $this->assertEquals($expectedId, $formView->vars['attr']['id']);
    }

    /**
     * @return array
     */
    public function dataSetUniqueLineItemFormId()
    {
        $formView = new FormView();
        $formView->vars['id'] = 'product_form';
        return [
            [$formView, $this->getEntity(Product::class, ['id' => 1]), 'product_form-product-id-1'],
            [$formView, $this->getEntity(Product::class, ['id' => null]), 'product_form'],
            [$formView, $this->getEntity(Product::class), 'product_form'],
            [$formView, ['id' => 1], 'product_form-product-id-1'],
            [$formView, ['id' => null], 'product_form'],
            [$formView, [], 'product_form'],
            [$formView, null, 'product_form'],
        ];
    }
}

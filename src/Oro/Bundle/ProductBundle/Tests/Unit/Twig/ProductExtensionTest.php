<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Twig;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Expression\Autocomplete\AutocompleteFieldsProvider;
use Oro\Bundle\ProductBundle\Twig\ProductExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ProductExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var AutocompleteFieldsProvider */
    protected $autocompleteFieldsProvider;

    /** @var ProductExtension */
    protected $extension;

    protected function setUp()
    {
        $this->autocompleteFieldsProvider = $this->getMockBuilder(AutocompleteFieldsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_product.autocomplete_fields_provider', $this->autocompleteFieldsProvider)
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
}

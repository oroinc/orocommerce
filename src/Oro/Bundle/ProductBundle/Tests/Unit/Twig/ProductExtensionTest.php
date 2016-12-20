<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Twig;

use Oro\Bundle\ProductBundle\Expression\Autocomplete\AutocompleteFieldsProvider;
use Oro\Bundle\ProductBundle\Twig\ProductExtension;

class ProductExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AutocompleteFieldsProvider
     */
    protected $autocompleteFieldsProvider;

    /**
     * @var ProductExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->autocompleteFieldsProvider = $this->getMockBuilder(AutocompleteFieldsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new ProductExtension($this->autocompleteFieldsProvider);
    }

    public function testGetName()
    {
        $this->assertEquals(ProductExtension::NAME, $this->extension->getName());
    }

    public function testGetFunctions()
    {
        $expectedFunctions = [
            ['oro_product_expression_autocomplete_data', [$this->autocompleteFieldsProvider, 'getAutocompleteData']]
        ];
        /** @var \Twig_SimpleFunction[] $actualFunctions */
        $actualFunctions = $this->extension->getFunctions();
        $this->assertSameSize($expectedFunctions, $actualFunctions);

        foreach ($actualFunctions as $twigFunction) {
            $expectedFunction = current($expectedFunctions);

            $this->assertInstanceOf('\Twig_SimpleFunction', $twigFunction);
            $this->assertEquals($expectedFunction[0], $twigFunction->getName());
            $this->assertEquals($expectedFunction[1], $twigFunction->getCallable());

            next($expectedFunctions);
        }
    }
}

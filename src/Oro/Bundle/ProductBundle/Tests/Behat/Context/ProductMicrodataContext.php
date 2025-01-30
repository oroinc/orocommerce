<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ProductMicrodataContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * Example: Then "Product Item View" should contains microdata:
     *              | Product Type Microdata Declaration  |
     *              | Product Brand Microdata Declaration |
     *              | Product Price Microdata Declaration |
     *              | SchemaOrg Description               |
     *
     * Example: And "Product Item View" should not contains microdata:
     *              | SchemaOrg Brand Name                |
     *              | SchemaOrg Price Currency            |
     *              | SchemaOrg Price                     |
     *
     * @Then /^"(?P<block>[^"]*)" should(?P<neg>(\s| not ))contains microdata:$/
     */
    public function assertBlockMicrodata(
        TableNode $table,
        string $neg,
        string $block
    ) {
        $this->assertMicrodataElements(
            $table,
            empty(trim($neg)),
            $this->findBlock($block),
            $block
        );
    }

    /**
     * Example: Then "productkit1" product in "Product Frontend Grid" should contains microdata:
     *              | Product Type Microdata Declaration  |
     *              | Product Brand Microdata Declaration |
     *              | Product Price Microdata Declaration |
     *              | SchemaOrg Description               |
     *
     * Example: And "productkit1" product in "Product Frontend Grid" should not contains microdata:
     *              | SchemaOrg Brand Name                |
     *              | SchemaOrg Price Currency            |
     *              | SchemaOrg Price                     |
     *
     * @Then /^"(?P<sku>[^"]*)" product in "(?P<block>[^"]*)" should(?P<neg>(\s| not ))contains microdata:$/
     */
    public function assertProductItemMicrodata(
        TableNode $table,
        string $neg,
        string $block,
        string $sku,
    ) {
        $this->assertMicrodataElements(
            $table,
            empty(trim($neg)),
            $this->findProductItem($sku, $this->findBlock($block)),
            $block
        );
    }

    /**
     * Example: Then "Product Item View" should contains "SchemaOrg Description" with attributes:
     *            | content | Product Description1 |
     *            | name    | ~                    |
     * Where '~' is null (the attribute doesn't exist)
     *
     * @Then /^"(?P<block>[^"]*)" should contains "(?P<elementName>[^"]*)" with attributes:$/
     */
    public function assertBlockMicrodataAttributes(
        TableNode $table,
        string $elementName,
        string $block
    ) {
        $element = $this->createElement($elementName, $this->findBlock($block));
        self::assertTrue(
            $element->isIsset(),
            sprintf(
                "Element '%s' not found in '%s'",
                $elementName,
                $block
            )
        );

        $this->assertMicrodataElementAttributes($table, $element, sprintf("%s => %s", $block, $elementName));
    }

    /**
     * Example: Then "sku1" product in "Product Frontend Grid" should contains "SchemaOrg Description" with attributes:
     *            | content | Product Description1 |
     *            | name    | ~                    |
     * Where '~' is null (the attribute doesn't exist)
     *
     * @Then /^"(?P<sku>[^"]*)" product in "(?P<block>[^"]*)" should contains "(?P<element>[^"]*)" with attributes:$/
     */
    public function assertProductItemMicrodataAttributes(
        TableNode $table,
        string $elementName,
        string $block,
        string $sku
    ) {
        $element = $this->createElement(
            $elementName,
            $this->findProductItem($sku, $this->findBlock($block))
        );
        self::assertTrue(
            $element->isIsset(),
            sprintf(
                "Element '%s' not found in '%s'",
                $elementName,
                $block
            )
        );

        $this->assertMicrodataElementAttributes(
            $table,
            $element,
            sprintf('%s => %s => %s', $block, $sku, $elementName)
        );
    }

    /**
     * Example: Then "Product Item View" should contains microdata elements with text:
     *            | SchemaOrg Price Currency | USD     |
     *            | SchemaOrg Price          | 10.00   |
     *
     * @Then /^"(?P<block>[^"]*)" should contains microdata elements with text:$/
     */
    public function assertBlockMicrodataText(TableNode $table, string $block)
    {
        $this->assertMicrodataElementText(
            $table,
            $this->findBlock($block),
            $block
        );
    }

    /**
     * Example: Then "PSKU1" product in "Product Frontend Grid" should contains microdata elements with text:
     *            | SchemaOrg Price Currency | USD     |
     *            | SchemaOrg Price          | 10.00   |
     *
     * @Then /^"(?P<sku>[^"]*)" product in "(?P<block>[^"]*)" should contains microdata elements with text:$/
     */
    public function assertProductItemMicrodataText(TableNode $table, string $block, string $sku)
    {
        $this->assertMicrodataElementText(
            $table,
            $this->findProductItem($sku, $this->findBlock($block)),
            sprintf("%s => %s", $block, $sku)
        );
    }

    private function findBlock(string $block): Element
    {
        $blockElement = $this->createElement($block);
        self::assertTrue($blockElement->isIsset(), sprintf("Block '%s' is not found.", $block));

        return $blockElement;
    }

    private function findProductItem(string $sku, ?Element $context = null): Element
    {
        $productItem = $this->findElementContains('ProductItem', $sku, $context);
        if (!$productItem) {
            $productItem = $this->findElementContains('Product Item View', $sku, $context);
        }

        self::assertNotNull($productItem, sprintf("Product with SKU '%s' not found", $sku));

        return $productItem;
    }

    private function assertMicrodataElements(
        TableNode $table,
        bool $isMicrodataShouldBePresented,
        Element $block,
        string $blockName
    ): void {
        foreach ($table->getRows() as $row) {
            [$elementName] = $row;
            $element = $this->createElement($elementName, $block);
            if ($isMicrodataShouldBePresented) {
                self::assertTrue(
                    $element->isIsset(),
                    sprintf(
                        "Microdata element '%s' not found in '%s'",
                        $elementName,
                        $blockName
                    )
                );
            } else {
                self::assertFalse(
                    $element->isIsset(),
                    sprintf(
                        "Microdata element '%s' found in '%s'",
                        $elementName,
                        $blockName
                    )
                );
            }
        }
    }

    private function assertMicrodataElementAttributes(
        TableNode $table,
        Element $block,
        string $blockName
    ): void {
        foreach ($table->getRows() as $row) {
            [$attributeName, $expectedValue] = $row;
            $attribute = $block->getAttribute($attributeName);

            if ($expectedValue !== '~') {
                self::assertNotNull(
                    $attribute,
                    sprintf(
                        "Attribute with name '%s' not found for '%s'",
                        $attributeName,
                        $blockName
                    )
                );
                static::assertStringContainsString($expectedValue, $attribute);
            } else {
                self::assertNull(
                    $attribute,
                    sprintf(
                        "Attribute with name '%s' shouldn't exist for '%s'",
                        $attributeName,
                        $blockName
                    )
                );
            }
        }
    }

    private function assertMicrodataElementText(
        TableNode $table,
        Element $block,
        string $blockName
    ): void {
        foreach ($table->getRows() as $row) {
            [$elementName, $elementText] = $row;
            $element = $this->createElement($elementName, $block);
            self::assertTrue(
                $element->isIsset(),
                sprintf(
                    "Microdata element '%s' not found in block '%s'",
                    $elementName,
                    $blockName
                )
            );
            $elementText = $this->fixStepArgument($elementText);
            self::assertTrue(
                mb_stripos($element->getHtml(), $elementText) !== false,
                sprintf(
                    "Microdata element '%s' in '%s' does not contain expected text '%s', given: '%s'",
                    $elementName,
                    $blockName,
                    $elementText,
                    $element->getHtml()
                )
            );
        }
    }
}

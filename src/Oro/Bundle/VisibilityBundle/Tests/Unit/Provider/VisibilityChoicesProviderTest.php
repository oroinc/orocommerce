<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Provider\VisibilityChoicesProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class VisibilityChoicesProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var VisibilityChoicesProvider */
    private $provider;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                static function (string $key) {
                    return sprintf('[trans]%s[/trans]', $key);
                }
            );

        $this->provider = new VisibilityChoicesProvider($translator);
    }

    /**
     * @return Category
     */
    private function createCategory()
    {
        $category = new Category();
        $category->setParentCategory(new Category());

        return $category;
    }

    public function testGetFormattedChoices()
    {
        $actual = $this->provider->getFormattedChoices(CategoryVisibility::class, $this->createCategory());
        $expected = [
            '[trans]oro.visibility.categoryvisibility.choice.parent_category[/trans]' => 'parent_category',
            '[trans]oro.visibility.categoryvisibility.choice.config[/trans]'          => 'config',
            '[trans]oro.visibility.categoryvisibility.choice.hidden[/trans]'          => 'hidden',
            '[trans]oro.visibility.categoryvisibility.choice.visible[/trans]'         => 'visible',
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testGetChoicesForCategory()
    {
        $actual = $this->provider->getChoices(CategoryVisibility::class, $this->createCategory());
        $this->assertEquals(
            ['parent_category', 'config', 'hidden', 'visible'],
            $actual
        );
    }

    public function testGetChoicesForProductWithCategory()
    {
        $product = new Product();
        $product->setCategory(new Category());

        $actual = $this->provider->getChoices(ProductVisibility::class, $product);
        $this->assertEquals(
            ['category', 'config', 'hidden', 'visible'],
            $actual
        );
    }

    public function testGetChoicesForProductWithoutCategory()
    {
        $product = new Product();

        $actual = $this->provider->getChoices(ProductVisibility::class, $product);
        $this->assertEquals(
            ['config', 'hidden', 'visible'],
            $actual
        );
    }

    public function testFormatChoices()
    {
        $actual = $this->provider->formatChoices('test.%s', ['test_1', 'test_2']);
        $expected = [
            '[trans]test.test_1[/trans]' => 'test_1',
            '[trans]test.test_2[/trans]' => 'test_2',
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testFormat()
    {
        $actual = $this->provider->format('test.%s', 'test_1');
        $this->assertEquals('[trans]test.test_1[/trans]', $actual);
    }
}

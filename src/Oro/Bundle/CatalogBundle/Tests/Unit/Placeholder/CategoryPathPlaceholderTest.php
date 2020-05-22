<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Placeholder;

use Oro\Bundle\CatalogBundle\Placeholder\CategoryPathPlaceholder;

class CategoryPathPlaceholderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CategoryPathPlaceholder */
    protected $placeholder;

    protected function setUp(): void
    {
        $this->placeholder = new CategoryPathPlaceholder();
    }

    public function testGetPlaceholder()
    {
        $this->assertIsString($this->placeholder->getPlaceholder());
        $this->assertEquals(CategoryPathPlaceholder::NAME, $this->placeholder->getPlaceholder());
    }

    public function testReplaceDefault()
    {
        $this->assertEquals(
            'category_path_',
            $this->placeholder->replaceDefault('category_path_' . CategoryPathPlaceholder::NAME)
        );
    }

    public function testReplace()
    {
        $this->assertEquals(
            'category_path_1_2_3',
            $this->placeholder->replace(
                'category_path_' . CategoryPathPlaceholder::NAME,
                [CategoryPathPlaceholder::NAME => '1_2_3']
            )
        );
    }

    public function testReplaceWithoutValue()
    {
        $this->assertEquals(
            'category_path_' . CategoryPathPlaceholder::NAME,
            $this->placeholder->replace(
                'category_path_' . CategoryPathPlaceholder::NAME,
                ['NOT_' . CategoryPathPlaceholder::NAME => '1_2_3']
            )
        );
    }
}

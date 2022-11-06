<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\CMSBundle\Attribute\Type\WYSIWYGAttributeType;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type\AttributeTypeTestCase;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;

class WYSIWYGAttributeTypeTest extends AttributeTypeTestCase
{
    /** @var HtmlTagHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $htmlTagHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $this->htmlTagHelper->expects($this->any())
            ->method('stripTags')
            ->willReturnCallback(function ($value) {
                return $value . ' stripped';
            });
    }

    /**
     * {@inheritdoc}
     */
    protected function getAttributeType(): AttributeTypeInterface
    {
        return new WYSIWYGAttributeType($this->htmlTagHelper);
    }

    /**
     * {@inheritdoc}
     */
    public function configurationMethodsDataProvider(): array
    {
        return [
            ['isSearchable' => true, 'isFilterable' => true, 'isSortable' => false]
        ];
    }

    public function testGetSearchableValue()
    {
        $this->assertSame(
            'text stripped',
            $this->getAttributeType()->getSearchableValue($this->attribute, 'text', $this->localization)
        );
    }

    public function testGetFilterableValue()
    {
        $this->assertSame(
            'text stripped',
            $this->getAttributeType()->getFilterableValue($this->attribute, 'text', $this->localization)
        );
    }

    public function testGetSortableValue()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not supported');

        $this->getAttributeType()->getSortableValue($this->attribute, 'text', $this->localization);
    }
}

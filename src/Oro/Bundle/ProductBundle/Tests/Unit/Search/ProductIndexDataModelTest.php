<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Search;

use Oro\Bundle\ProductBundle\Search\ProductIndexDataModel;

class ProductIndexDataModelTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters()
    {
        $fieldName = 'test_field';
        $value = new \stdClass();
        $placeholders = ['TEST_PLACEHOLDER' => 'placeholder_value'];
        $localized = true;
        $searchable = true;

        $model = new ProductIndexDataModel($fieldName, $value, $placeholders, $localized, $searchable);

        $this->assertSame($fieldName, $model->getFieldName());
        $this->assertSame($value, $model->getValue());
        $this->assertSame($placeholders, $model->getPlaceholders());
        $this->assertSame($localized, $model->isLocalized());
        $this->assertSame($searchable, $model->isSearchable());
    }
}

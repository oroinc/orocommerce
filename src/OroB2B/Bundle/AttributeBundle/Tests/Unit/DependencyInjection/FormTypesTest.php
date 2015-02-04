<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use OroB2B\Bundle\AttributeBundle\DependencyInjection\OroB2BAttributeExtension;

class FormTypesTest extends ExtensionTestCase
{

    public function testLoad()
    {
        $this->loadExtension(new OroB2BAttributeExtension());

        $expectedParameters = [
//            'orob2b_attribute.form.type.attribute.class',
            'orob2b_attribute.form.type.attribute_property_fallback.class',
            'orob2b_attribute.form.type.fallback_value.class',
            'orob2b_attribute.form.type.attribute_type.class',
            'orob2b_attribute.form.type.attribute_type_constraint.class',
            'orob2b_attribute.form.type.locale_collection.class',
            'orob2b_attribute.form.type.localized_attribute_property.class',
            'orob2b_attribute.form.type.website_collection.class',
            'orob2b_attribute.form.type.website_attribute_property.class',
            'orob2b_attribute.form.type.init.class',
            'orob2b_attribute.form.extension.integer.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
//            'orob2b_attribute.form.type.attribute',
            'orob2b_attribute.form.type.attribute_property_fallback',
            'orob2b_attribute.form.type.fallback_value',
            'orob2b_attribute.form.type.attribute_type',
            'orob2b_attribute.form.type.attribute_type_constraint',
            'orob2b_attribute.form.type.locale_collection',
            'orob2b_attribute.form.type.localized_attribute_property',
            'orob2b_attribute.form.type.website_collection',
            'orob2b_attribute.form.type.website_attribute_property',
            'orob2b_attribute.form.type.init',
            'orob2b_attribute.form.extension.integer',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}

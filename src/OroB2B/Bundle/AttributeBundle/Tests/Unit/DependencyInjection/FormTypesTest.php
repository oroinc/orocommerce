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
            'orob2b_attribute.form.type.attribute_type.class',
            'orob2b_attribute.form.type.attribute_type_constraint.class',
            'orob2b_attribute.form.type.create.class',
            'orob2b_attribute.form.extension.integer.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            'orob2b_attribute.form.type.attribute_type',
            'orob2b_attribute.form.type.attribute_type_constraint',
            'orob2b_attribute.form.type.create',
            'orob2b_attribute.form.extension.integer',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}

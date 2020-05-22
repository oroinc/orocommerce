<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider\SerializedFieldProviderTest as BaseSerializedFieldProviderTest;
use Oro\Bundle\ProductBundle\Provider\SerializedFieldProvider;

class SerializedFieldProviderTest extends BaseSerializedFieldProviderTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->serializedFieldProvider = new SerializedFieldProvider($this->extendConfigProvider);
    }

    public function testIsSerializedBooleanAttribute()
    {
        $fieldConfigModel = new FieldConfigModel('name', 'boolean');
        $fieldConfigModel->fromArray('attribute', ['is_attribute' => true]);

        $this->extendConfigProvider
            ->expects($this->never())
            ->method('getPropertyConfig');

        $this->assertFalse($this->serializedFieldProvider->isSerialized($fieldConfigModel));
    }

    public function testIsSerializedBooleanField()
    {
        $this->assertExtendConfigProvider();
        $this->assertTrue($this->serializedFieldProvider->isSerialized(new FieldConfigModel('name', 'boolean')));
    }

    public function testIsSerializedNotBooleanAttribute()
    {
        $this->assertExtendConfigProvider();

        $fieldConfigModel = new FieldConfigModel('name', 'string');
        $fieldConfigModel->fromArray('attribute', ['is_attribute' => true]);

        $this->assertTrue($this->serializedFieldProvider->isSerialized($fieldConfigModel));
    }

    public function testIsSerializedNotBooleanField()
    {
        $this->assertExtendConfigProvider();
        $this->assertTrue($this->serializedFieldProvider->isSerialized(new FieldConfigModel('name', 'string')));
    }
}

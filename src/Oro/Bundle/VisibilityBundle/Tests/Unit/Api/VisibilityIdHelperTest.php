<?php

declare(strict_types=1);

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Api;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\VisibilityBundle\Api\VisibilityIdHelper;

class VisibilityIdHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var VisibilityIdHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->helper = new VisibilityIdHelper();
    }

    public function testGetIdShouldThrowInvalidArgumentException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "propertyPath" item does not exist in a composite visibility identifier.');
        $this->helper->getId([], 'propertyPath');
    }

    public function testGetId()
    {
        self::assertSame(1, $this->helper->getId(['propertyPath' => 1], 'propertyPath'));
    }

    public function testEncodeVisibilityIdShouldNotFailIfNoDependsOnFields()
    {
        $idFieldConfig = $this->createMock(EntityDefinitionFieldConfig::class);
        $idFieldConfig->expects(self::once())
            ->method('getDependsOn')
            ->willReturn(null);

        self::assertSame('', $this->helper->encodeVisibilityId(['propertyPath' => 1], $idFieldConfig));
    }

    public function testEncodeVisibilityIdShouldThrowInvalidArgumentExceptionIfNoValueForVisibilityIdentifier()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A value for "propertyPath" must exist in a visibility identifier.');

        $idFieldConfig = $this->createMock(EntityDefinitionFieldConfig::class);
        $idFieldConfig->expects(self::once())
            ->method('getDependsOn')
            ->willReturn(['propertyPath']);

        self::assertSame('', $this->helper->encodeVisibilityId(['propertyPath2' => 2], $idFieldConfig));
    }

    public function testEncodeVisibilityIdShouldThrowInvalidArgumentExceptionIfNullValueForVisibilityIdentifier()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A value for "propertyPath" in a visibility identifier must be not null.');

        $idFieldConfig = $this->createMock(EntityDefinitionFieldConfig::class);
        $idFieldConfig->expects(self::once())
            ->method('getDependsOn')
            ->willReturn(['propertyPath']);

        self::assertSame('', $this->helper->encodeVisibilityId(['propertyPath' => null], $idFieldConfig));
    }

    public function testEncodeVisibilityIdShouldThrowInvalidArgumentExceptionIfNotApplicableValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'A value for "propertyPath" in a visibility identifier must be an integer greater than or equals to zero.'
        );

        $idFieldConfig = $this->createMock(EntityDefinitionFieldConfig::class);
        $idFieldConfig->expects(self::once())
            ->method('getDependsOn')
            ->willReturn(['propertyPath']);

        self::assertSame('', $this->helper->encodeVisibilityId(['propertyPath' => 'abc'], $idFieldConfig));
    }

    public function testEncodeVisibilityIdShouldThrowInvalidArgumentExceptionIfNegativeValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'A value for "propertyPath" in a visibility identifier must be an integer greater than or equals to zero.'
        );

        $idFieldConfig = $this->createMock(EntityDefinitionFieldConfig::class);
        $idFieldConfig->expects(self::once())
            ->method('getDependsOn')
            ->willReturn(['propertyPath']);

        self::assertSame('', $this->helper->encodeVisibilityId(['propertyPath' => -1], $idFieldConfig));
    }

    public function testEncodeVisibilityIdShouldSupportZeroValueAndMultiplePropertyPaths()
    {
        $idFieldConfig = $this->createMock(EntityDefinitionFieldConfig::class);
        $idFieldConfig->expects(self::once())
            ->method('getDependsOn')
            ->willReturn(['propertyPath', 'propertyPath2']);

        self::assertSame('0-1', $this->helper->encodeVisibilityId([
            'propertyPath' => 0,
            'propertyPath2' => 1,
        ], $idFieldConfig));
    }

    public function testDecodeVisibilityIdShouldNotFailIfNoDependsOnFields()
    {
        $idFieldConfig = $this->createMock(EntityDefinitionFieldConfig::class);
        $idFieldConfig->expects(self::once())
            ->method('getDependsOn')
            ->willReturn(null);

        self::assertNull($this->helper->decodeVisibilityId('propertyPath', $idFieldConfig));
    }

    public function testDecodeVisibilityId()
    {
        $idFieldConfig = $this->createMock(EntityDefinitionFieldConfig::class);
        $idFieldConfig->expects(self::once())
            ->method('getDependsOn')
            ->willReturn(null);

        self::assertNull($this->helper->decodeVisibilityId('propertyPath', $idFieldConfig));
    }
}

<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DBAL\Types;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGPropertiesType;
use Oro\Component\TestUtils\ORM\Mocks\DatabasePlatformMock;

class WYSIWYGPropertiesTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var Type */
    private $type;

    public static function setUpBeforeClass()
    {
        Type::addType('wysiwyg_properties', WYSIWYGPropertiesType::class);
    }

    protected function setUp()
    {
        $this->type = Type::getType('wysiwyg_properties');
    }

    public function testSuffixConst(): void
    {
        $this->assertEquals('_properties', WYSIWYGPropertiesType::TYPE_SUFFIX);
    }

    public function testGetName(): void
    {
        $this->assertEquals('wysiwyg_properties', $this->type->getName());
    }

    public function testRequiresSQLCommentHint(): void
    {
        /** @var DatabasePlatformMock $platform */
        $platform = $this->createMock(DatabasePlatformMock::class);

        $this->assertTrue($this->type->requiresSQLCommentHint($platform));
    }

    /**
     * @param mixed $value
     * @param mixed $expectedValue
     * @dataProvider getConvertToDatabaseValueDataProvider
     * @depends testGetName
     */
    public function testConvertToDatabaseValue($value, $expectedValue): void
    {
        $typeName = 'wysiwyg_properties';
        $type = Type::getType($typeName);
        $platform = new DatabasePlatformMock();

        $this->assertEquals($expectedValue, $type->convertToDatabaseValue($value, $platform));
    }

    public function getConvertToDatabaseValueDataProvider()
    {
        return [
            'string' => [
                'value' => 'text value',
                'expectedValue' => 'text value'
            ],
            'object' => [
                'value' => new \stdClass(),
                'expectedValue' => json_encode(new \stdClass())
            ],
            'null' => [
                'value' => null,
                'expectedValue' => null
            ],
        ];
    }

    /**
     * @param mixed $value
     * @param mixed $expectedValue
     * @dataProvider getConvertToPHPValueDataProvider
     * @depends testGetName
     */
    public function testConvertToPHPValue($value, $expectedValue): void
    {
        $typeName = 'wysiwyg_properties';
        $type = Type::getType($typeName);
        $platform = new DatabasePlatformMock();

        $this->assertEquals($expectedValue, $type->convertToPHPValue($value, $platform));
    }

    public function getConvertToPHPValueDataProvider()
    {
        return [
            'string' => [
                'value' => 'text value',
                'expectedValue' => 'text value'
            ],
            'object' => [
                'value' => '{}',
                'expectedValue' => '{}'
            ],
            'null' => [
                'value' => null,
                'expectedValue' => null
            ],
        ];
    }
}

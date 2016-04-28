<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\DBAL\Types;

use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;
use Oro\Component\TestUtils\ORM\Mocks\DatabasePlatformMock;

use OroB2B\Bundle\PaymentBundle\DBAL\Types\SecureArrayType;

class SecureArrayTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var SecureArrayType */
    protected $type;

    /** @var Mcrypt */
    protected $mcrypt;

    public static function setUpBeforeClass()
    {
        SecureArrayType::addType(
            SecureArrayType::TYPE,
            'OroB2B\Bundle\PaymentBundle\DBAL\Types\SecureArrayType'
        );
    }

    protected function setUp()
    {
        $this->type = SecureArrayType::getType(SecureArrayType::TYPE);

        $this->mcrypt = new Mcrypt('key');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Mcrypt missing
     */
    public function testMcryptMissingError()
    {
        $platform = new DatabasePlatformMock();

        $this->type->convertToPHPValue('encoded_string', $platform);
    }

    public function testConvertToPHPValue()
    {
        /** @var SecureArrayType $secureArrayType */
        $secureArrayType = SecureArrayType::getType(SecureArrayType::TYPE);
        $secureArrayType->setMcrypt($this->mcrypt);

        $value = ['value' => 'value'];
        $platform = new DatabasePlatformMock();

        $encrypted = $this->mcrypt->encryptData(json_encode($value));

        $this->assertEquals(
            $value,
            $this->type->convertToPHPValue($encrypted, $platform)
        );
    }

    public function testConvertToPHPValueEmpty()
    {
        /** @var SecureArrayType $secureArrayType */
        $secureArrayType = SecureArrayType::getType(SecureArrayType::TYPE);
        $secureArrayType->setMcrypt($this->mcrypt);

        $platform = new DatabasePlatformMock();

        $this->assertEquals([], $this->type->convertToPHPValue(null, $platform));
        $this->assertEquals([], $this->type->convertToPHPValue('', $platform));
    }

    public function testConvertToPHPValueInvalidJson()
    {
        /** @var SecureArrayType $secureArrayType */
        $secureArrayType = SecureArrayType::getType(SecureArrayType::TYPE);
        $secureArrayType->setMcrypt($this->mcrypt);

        $platform = new DatabasePlatformMock();

        $encrypted = $this->mcrypt->encryptData('{"value":"value}');

        $this->assertNull($this->type->convertToPHPValue($encrypted, $platform));
    }

    public function testConvertToDatabaseValue()
    {
        /** @var SecureArrayType $secureArrayType */
        $secureArrayType = SecureArrayType::getType(SecureArrayType::TYPE);
        $secureArrayType->setMcrypt($this->mcrypt);

        $value = ['value' => 'value'];
        $platform = new DatabasePlatformMock();

        $this->assertNotEquals(
            $value,
            $this->type->convertToDatabaseValue($value, $platform)
        );

        $this->assertNotEquals(
            json_encode($value),
            $this->type->convertToDatabaseValue($value, $platform)
        );
    }

    public function testConvertToDatabaseValueNull()
    {
        /** @var SecureArrayType $secureArrayType */
        $secureArrayType = SecureArrayType::getType(SecureArrayType::TYPE);
        $secureArrayType->setMcrypt($this->mcrypt);

        $platform = new DatabasePlatformMock();

        $this->assertNull($this->type->convertToDatabaseValue(null, $platform));
    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->type->getName());
    }

    public function testTextIsUsedToStoreData()
    {
        /** @var DatabasePlatformMock|\PHPUnit_Framework_MockObject_MockObject $platform */
        $platform = $this->getMock('Oro\Component\TestUtils\ORM\Mocks\DatabasePlatformMock');

        $platform->expects($this->once())->method('getClobTypeDeclarationSQL');

        $this->type->getSQLDeclaration([], $platform);
    }

    public function testRequiresSQLCommentHint()
    {
        /** @var DatabasePlatformMock|\PHPUnit_Framework_MockObject_MockObject $platform */
        $platform = $this->getMock('Oro\Component\TestUtils\ORM\Mocks\DatabasePlatformMock');
        $this->assertTrue($this->type->requiresSQLCommentHint($platform));
    }
}

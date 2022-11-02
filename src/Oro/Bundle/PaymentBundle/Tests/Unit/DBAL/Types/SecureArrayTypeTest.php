<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\DBAL\Types;

use Oro\Bundle\PaymentBundle\DBAL\Types\SecureArrayType;
use Oro\Bundle\SecurityBundle\Encoder\DefaultCrypter;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Component\TestUtils\ORM\Mocks\DatabasePlatformMock;

class SecureArrayTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var SecureArrayType */
    private $type;

    /** @var SymmetricCrypterInterface */
    private $crypter;

    public static function setUpBeforeClass(): void
    {
        SecureArrayType::addType(
            SecureArrayType::TYPE,
            SecureArrayType::class
        );
    }

    protected function setUp(): void
    {
        $this->type = SecureArrayType::getType(SecureArrayType::TYPE);

        $this->crypter = new DefaultCrypter('key');
    }

    public function testMcryptMissingError()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Crypter is not set');

        $platform = new DatabasePlatformMock();

        $this->type->convertToPHPValue('encoded_string', $platform);
    }

    public function testConvertToPHPValue()
    {
        /** @var SecureArrayType $secureArrayType */
        $secureArrayType = SecureArrayType::getType(SecureArrayType::TYPE);
        $secureArrayType->setCrypter($this->crypter);

        $value = ['value' => 'value'];
        $platform = new DatabasePlatformMock();

        $encrypted = $this->crypter->encryptData(json_encode($value, JSON_THROW_ON_ERROR));

        $this->assertEquals(
            $value,
            $this->type->convertToPHPValue($encrypted, $platform)
        );
    }

    public function testConvertToPHPValueEmpty()
    {
        /** @var SecureArrayType $secureArrayType */
        $secureArrayType = SecureArrayType::getType(SecureArrayType::TYPE);
        $secureArrayType->setCrypter($this->crypter);

        $platform = new DatabasePlatformMock();

        $this->assertEquals([], $this->type->convertToPHPValue(null, $platform));
        $this->assertEquals([], $this->type->convertToPHPValue('', $platform));
    }

    public function testConvertToPHPValueInvalidJson()
    {
        /** @var SecureArrayType $secureArrayType */
        $secureArrayType = SecureArrayType::getType(SecureArrayType::TYPE);
        $secureArrayType->setCrypter($this->crypter);

        $platform = new DatabasePlatformMock();

        $encrypted = $this->crypter->encryptData('{"value":"value}');

        $this->assertNull($this->type->convertToPHPValue($encrypted, $platform));
    }

    public function testConvertToDatabaseValue()
    {
        /** @var SecureArrayType $secureArrayType */
        $secureArrayType = SecureArrayType::getType(SecureArrayType::TYPE);
        $secureArrayType->setCrypter($this->crypter);

        $value = ['value' => 'value'];
        $platform = new DatabasePlatformMock();

        $this->assertNotEquals(
            $value,
            $this->type->convertToDatabaseValue($value, $platform)
        );

        $this->assertNotEquals(
            json_encode($value, JSON_THROW_ON_ERROR),
            $this->type->convertToDatabaseValue($value, $platform)
        );
    }

    public function testConvertToDatabaseValueNull()
    {
        /** @var SecureArrayType $secureArrayType */
        $secureArrayType = SecureArrayType::getType(SecureArrayType::TYPE);
        $secureArrayType->setCrypter($this->crypter);

        $platform = new DatabasePlatformMock();

        $this->assertNull($this->type->convertToDatabaseValue(null, $platform));
    }

    public function testTextIsUsedToStoreData()
    {
        $platform = $this->createMock(DatabasePlatformMock::class);

        $platform->expects($this->once())
            ->method('getClobTypeDeclarationSQL');

        $this->type->getSQLDeclaration([], $platform);
    }

    public function testRequiresSQLCommentHint()
    {
        $platform = $this->createMock(DatabasePlatformMock::class);
        $this->assertTrue($this->type->requiresSQLCommentHint($platform));
    }
}

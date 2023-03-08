<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Bundle\PaymentBundle\DBAL\Types\SecureArrayType;
use Oro\Bundle\SecurityBundle\Encoder\DefaultCrypter;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

class SecureArrayTypeTest extends \PHPUnit\Framework\TestCase
{
    private SymmetricCrypterInterface $crypter;
    private SecureArrayType $type;

    protected function setUp(): void
    {
        $this->crypter = new DefaultCrypter('key');

        $this->type = new SecureArrayType();
    }

    public function testMcryptMissingError()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Crypter is not set');

        $this->type->convertToPHPValue('encoded_string', $this->createMock(AbstractPlatform::class));
    }

    public function testConvertToPHPValue()
    {
        $this->type->setCrypter($this->crypter);

        $value = ['value' => 'value'];

        $encrypted = $this->crypter->encryptData(json_encode($value, JSON_THROW_ON_ERROR));

        $this->assertEquals(
            $value,
            $this->type->convertToPHPValue($encrypted, $this->createMock(AbstractPlatform::class))
        );
    }

    public function testConvertToPHPValueEmpty()
    {
        $this->type->setCrypter($this->crypter);

        $platform = $this->createMock(AbstractPlatform::class);

        $this->assertEquals([], $this->type->convertToPHPValue(null, $platform));
        $this->assertEquals([], $this->type->convertToPHPValue('', $platform));
    }

    public function testConvertToPHPValueInvalidJson()
    {
        $this->type->setCrypter($this->crypter);

        $encrypted = $this->crypter->encryptData('{"value":"value}');

        $this->assertNull($this->type->convertToPHPValue($encrypted, $this->createMock(AbstractPlatform::class)));
    }

    public function testConvertToDatabaseValue()
    {
        $this->type->setCrypter($this->crypter);

        $value = ['value' => 'value'];
        $platform = $this->createMock(AbstractPlatform::class);

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
        $this->type->setCrypter($this->crypter);

        $this->assertNull($this->type->convertToDatabaseValue(null, $this->createMock(AbstractPlatform::class)));
    }

    public function testTextIsUsedToStoreData()
    {
        $platform = $this->createMock(AbstractPlatform::class);

        $platform->expects($this->once())
            ->method('getClobTypeDeclarationSQL');

        $this->type->getSQLDeclaration([], $platform);
    }

    public function testRequiresSQLCommentHint()
    {
        $this->assertTrue($this->type->requiresSQLCommentHint($this->createMock(AbstractPlatform::class)));
    }
}

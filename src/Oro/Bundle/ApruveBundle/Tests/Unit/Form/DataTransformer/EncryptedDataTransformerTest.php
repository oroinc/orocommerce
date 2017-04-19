<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ApruveBundle\Form\DataTransformer\EncryptedDataTransformer;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

class EncryptedDataTransformerTest extends \PHPUnit_Framework_TestCase
{
    const ENCRYPTED_STRING = 'encryptedSample';
    const DECRYPTED_STRING = 'sample';

    /**
     * @var SymmetricCrypterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $crypter;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);
    }

    /**
     * @dataProvider transformDataProvider
     *
     * @param string|null $value
     * @param bool $decrypt
     * @param string|null $expected
     */
    public function testTransform($value, $decrypt, $expected)
    {
        $this->crypter
            ->method('decryptData')
            ->with(self::ENCRYPTED_STRING)
            ->willReturn(self::DECRYPTED_STRING);

        $transformer = new EncryptedDataTransformer($this->crypter, $decrypt);

        $actual = $transformer->transform($value);

        static::assertSame($expected, $actual);
    }

    /**
     * @return array
     */
    public function transformDataProvider()
    {
        return [
            'when value is null, decrypt==true should not have any effect' => [null, true, null],
            'when value is null, decrypt==false should not have any effect too' => [null, false, null],

            'when decrypt is false' => [self::ENCRYPTED_STRING, false, self::ENCRYPTED_STRING],
            'when decrypt is true' => [self::ENCRYPTED_STRING, true, self::DECRYPTED_STRING],
        ];
    }

    public function testTransformWithException()
    {
        $this->crypter
            ->method('decryptData')
            ->willThrowException(new \Exception());

        $transformer = new EncryptedDataTransformer($this->crypter, true);

        $actual = $transformer->transform(self::ENCRYPTED_STRING);

        static::assertNull($actual);
    }

    /**
     * @dataProvider reverseTransformDataProvider
     *
     * @param string|null $value
     * @param string|null $expected
     */
    public function testReverseTransform($value, $expected)
    {
        $this->crypter
            ->method('encryptData')
            ->with(self::DECRYPTED_STRING)
            ->willReturn(self::ENCRYPTED_STRING);

        $transformer = new EncryptedDataTransformer($this->crypter, true);

        $actual = $transformer->reverseTransform($value);

        static::assertSame($expected, $actual);
    }

    /**
     * @return array
     */
    public function reverseTransformDataProvider()
    {
        return [
            'when value is null' => [null, null],

            'when value is string, should be encrypted' => [self::DECRYPTED_STRING, self::ENCRYPTED_STRING],
        ];
    }
}

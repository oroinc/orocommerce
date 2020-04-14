<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Model;

use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\SaleBundle\Model\ContactInfo;
use Oro\Bundle\SaleBundle\Model\ContactInfoFactory;
use Oro\Bundle\UserBundle\Entity\User;

class ContactInfoFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContactInfoFactory
     */
    private $factory;

    /**
     * @var NameFormatter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $nameFormatter;

    protected function setUp(): void
    {
        $this->nameFormatter = $this->createMock(NameFormatter::class);
        $this->factory = new ContactInfoFactory($this->nameFormatter);
    }

    public function testCreateEmptyObject()
    {
        $contactInfo = $this->factory->createContactInfo();
        static::assertInstanceOf(ContactInfo::class, $contactInfo);
        static::assertTrue($contactInfo->isEmpty());
    }

    public function testCreateFromUserObject()
    {
        $user = $this->getMockBuilder(User::class)
            ->setMethods(['getPhone', 'getEmail'])
            ->getMock();
        $user->method('getPhone')->willReturn('1111');
        $user->method('getEmail')->willReturn('mail@example.dev');
        $this->nameFormatter
            ->method('format')
            ->with($user)
            ->willReturn('John Doe');

        $contactInfo = $this->factory->createContactInfoByUser($user);
        static::assertInstanceOf(ContactInfo::class, $contactInfo);
        static::assertFalse($contactInfo->isEmpty());
        $expectedResult = [
            'email' => 'mail@example.dev',
            'phone' => '1111',
            'name' => 'John Doe',
        ];
        static::assertEquals($expectedResult, $contactInfo->all());
    }

    public function testCreateWithManualText()
    {
        $text = 'test text';
        $contactInfo = $this->factory->createContactInfoWithText($text);
        static::assertInstanceOf(ContactInfo::class, $contactInfo);
        static::assertFalse($contactInfo->isEmpty());
        static::assertEquals($text, $contactInfo->getManualText());
    }
}

<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Model;

use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\SaleBundle\Model\ContactInfo;
use Oro\Bundle\SaleBundle\Model\ContactInfoFactory;
use Oro\Bundle\UserBundle\Entity\User;

class ContactInfoFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContactInfoFactory */
    private $factory;

    /** @var NameFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $nameFormatter;

    protected function setUp(): void
    {
        $this->nameFormatter = $this->createMock(NameFormatter::class);
        $this->factory = new ContactInfoFactory($this->nameFormatter);
    }

    public function testCreateEmptyObject()
    {
        $contactInfo = $this->factory->createContactInfo();
        self::assertInstanceOf(ContactInfo::class, $contactInfo);
        self::assertTrue($contactInfo->isEmpty());
    }

    public function testCreateFromUserObject()
    {
        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['getEmail'])
            ->addMethods(['getPhone'])
            ->getMock();
        $user->expects(self::any())
            ->method('getPhone')
            ->willReturn('1111');
        $user->expects(self::any())
            ->method('getEmail')
            ->willReturn('mail@example.dev');
        $this->nameFormatter->expects(self::any())
            ->method('format')
            ->with($user)
            ->willReturn('John Doe');

        $contactInfo = $this->factory->createContactInfoByUser($user);
        self::assertInstanceOf(ContactInfo::class, $contactInfo);
        self::assertFalse($contactInfo->isEmpty());
        $expectedResult = [
            'email' => 'mail@example.dev',
            'phone' => '1111',
            'name' => 'John Doe',
        ];
        self::assertEquals($expectedResult, $contactInfo->all());
    }

    public function testCreateWithManualText()
    {
        $text = 'test text';
        $contactInfo = $this->factory->createContactInfoWithText($text);
        self::assertInstanceOf(ContactInfo::class, $contactInfo);
        self::assertFalse($contactInfo->isEmpty());
        self::assertEquals($text, $contactInfo->getManualText());
    }
}

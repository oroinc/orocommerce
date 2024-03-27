<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Provider;

use Oro\Bundle\SaleBundle\Model\ContactInfo;
use Oro\Bundle\SaleBundle\Provider\ContactInfoProviderInterface;
use Oro\Bundle\SaleBundle\Provider\EmailTemplateSystemVariablesProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailTemplateSystemVariablesProviderTest extends TestCase
{
    private ContactInfoProviderInterface|MockObject $contactInfoProvider;

    private EmailTemplateSystemVariablesProvider $provider;

    protected function setUp(): void
    {
        $this->contactInfoProvider = $this->createMock(ContactInfoProviderInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(static fn (string $key) => $key . '.translated');

        $this->provider = new EmailTemplateSystemVariablesProvider($this->contactInfoProvider, $translator);
    }

    public function testGetVariableDefinitions(): void
    {
        self::assertEquals(
            [
                'contactInfo' => [
                    'type' => 'array',
                    'label' => 'oro.sale.emailtemplate.contact_info.translated',
                ],
            ],
            $this->provider->getVariableDefinitions()
        );
    }

    public function testGetVariableValues(): void
    {
        $contactInfo = (new ContactInfo())
            ->setEmail('contact@example.com');
        $this->contactInfoProvider
            ->expects(self::once())
            ->method('getContactInfo')
            ->willReturn($contactInfo);

        self::assertEquals(
            ['contactInfo' => ['email' => $contactInfo->getEmail()]],
            $this->provider->getVariableValues()
        );
    }
}

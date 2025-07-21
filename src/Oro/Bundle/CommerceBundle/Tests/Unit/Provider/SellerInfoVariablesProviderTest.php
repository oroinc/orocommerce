<?php

declare(strict_types=1);

namespace Oro\Bundle\CommerceBundle\Tests\Unit\Provider;

use Oro\Bundle\CommerceBundle\Provider\SellerInfoProvider;
use Oro\Bundle\CommerceBundle\Provider\SellerInfoVariablesProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SellerInfoVariablesProviderTest extends TestCase
{
    private SellerInfoProvider&MockObject $sellerInfoProvider;
    private SellerInfoVariablesProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->sellerInfoProvider = $this->createMock(SellerInfoProvider::class);

        $this->provider = new SellerInfoVariablesProvider(
            $this->sellerInfoProvider,
            $translator
        );
    }

    public function testGetVariableDefinitions(): void
    {
        $expected = [
            'sellerCompanyName' => 'ORO',
            'sellerBusinessAddress' => 'City',
            'sellerPhoneNumber' => '123456789',
            'sellerContactEmail' => 'test@test.com',
            'sellerWebsiteURL' => 'http://localhost',
            'sellerTaxID' => '54321',
        ];

        $this->sellerInfoProvider
            ->expects(self::once())
            ->method('getSellerInfo')
            ->willReturn($expected);

        self::assertSame(
            [
                'sellerCompanyName' =>
                    ['type' => 'string', 'label' => 'oro.commerce.emailtemplate.seller_company_name'],
                'sellerBusinessAddress' =>
                    ['type' => 'string', 'label' => 'oro.commerce.emailtemplate.seller_business_address'],
                'sellerPhoneNumber' =>
                    ['type' => 'string', 'label' => 'oro.commerce.emailtemplate.seller_phone_number'],
                'sellerContactEmail' =>
                    ['type' => 'string', 'label' => 'oro.commerce.emailtemplate.seller_contact_email'],
                'sellerWebsiteURL' =>
                    ['type' => 'string', 'label' => 'oro.commerce.emailtemplate.seller_website_url'],
                'sellerTaxID' =>
                    ['type' => 'string', 'label' => 'oro.commerce.emailtemplate.seller_tax_id'],
            ],
            $this->provider->getVariableDefinitions()
        );
    }

    public function testGetVariableValues(): void
    {
        $expected = [
            'sellerCompanyName' => 'ORO',
            'sellerBusinessAddress' => 'City',
            'sellerPhoneNumber' => '123456789',
            'sellerContactEmail' => 'test@test.com',
            'sellerWebsiteURL' => 'http://localhost',
            'sellerTaxID' => '54321',
        ];

        $this->sellerInfoProvider
            ->expects(self::once())
            ->method('getSellerInfo')
            ->willReturn($expected);

        self::assertSame($expected, $this->provider->getVariableValues());
    }
}

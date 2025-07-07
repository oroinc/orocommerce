<?php

declare(strict_types=1);

namespace Oro\Bundle\CommerceBundle\Tests\Unit\Provider;

use Oro\Bundle\CommerceBundle\DependencyInjection\Configuration;
use Oro\Bundle\CommerceBundle\Provider\SellerInfoProvider;
use Oro\Bundle\CommerceBundle\Provider\SellerInfoVariablesProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SellerInfoVariablesProviderTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private SellerInfoProvider&MockObject $sellerInfoProvider;
    private SellerInfoVariablesProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->configManager = $this->createMock(ConfigManager::class);
        $this->sellerInfoProvider = $this->createMock(SellerInfoProvider::class);

        $this->provider = new SellerInfoVariablesProvider(
            $translator,
            $this->configManager
        );
        $this->provider->setSellerInfoProvider($this->sellerInfoProvider);
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

    public function testGetVariableDefinitionsWhenNoSellerInfoProvider(): void
    {
        $this->provider->setSellerInfoProvider(null);

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

    public function testGetVariableValuesWhenNoSellerInfoProvider(): void
    {
        $this->configManager
            ->expects(self::exactly(6))
            ->method('get')
            ->willReturnMap([
                [Configuration::getConfigKeyByName(Configuration::COMPANY_NAME), false, false, null, 'ORO'],
                [Configuration::getConfigKeyByName(Configuration::BUSINESS_ADDRESS), false, false, null, 'City'],
                [Configuration::getConfigKeyByName(Configuration::PHONE_NUMBER), false, false, null, '123456789'],
                [Configuration::getConfigKeyByName(Configuration::CONTACT_EMAIL), false, false, null, 'test@test.com'],
                [Configuration::getConfigKeyByName(Configuration::WEBSITE), false, false, null, 'http://localhost'],
                [Configuration::getConfigKeyByName(Configuration::TAX_ID), false, false, null, '54321'],
            ]);

        $this->provider->setSellerInfoProvider(null);

        self::assertSame([
            'sellerCompanyName' => 'ORO',
            'sellerBusinessAddress' => 'City',
            'sellerPhoneNumber' => '123456789',
            'sellerContactEmail' => 'test@test.com',
            'sellerWebsiteURL' => 'http://localhost',
            'sellerTaxID' => '54321',
        ], $this->provider->getVariableValues());
    }
}

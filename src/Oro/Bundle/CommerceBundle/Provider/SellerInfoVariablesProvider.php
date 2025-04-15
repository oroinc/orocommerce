<?php

namespace Oro\Bundle\CommerceBundle\Provider;

use Oro\Bundle\CommerceBundle\DependencyInjection\Configuration;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Twig\Sandbox\SystemVariablesProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides the following system variables for email templates:
 * * sellerCompanyName
 * * sellerBusinessAddress
 * * sellerPhoneNumber
 * * sellerContactEmail
 * * sellerWebsiteURL
 * * sellerTaxID
 */
class SellerInfoVariablesProvider implements SystemVariablesProviderInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ConfigManager $configManager,
    ) {
    }

    #[\Override]
    public function getVariableDefinitions(): array
    {
        return [
            'sellerCompanyName' => [
                'type'  => 'string',
                'label' => $this->translator->trans('oro.commerce.emailtemplate.seller_company_name')
            ],
            'sellerBusinessAddress' => [
                'type'  => 'string',
                'label' => $this->translator->trans('oro.commerce.emailtemplate.seller_business_address')
            ],
            'sellerPhoneNumber' => [
                'type'  => 'string',
                'label' => $this->translator->trans('oro.commerce.emailtemplate.seller_phone_number')
            ],
            'sellerContactEmail' => [
                'type'  => 'string',
                'label' => $this->translator->trans('oro.commerce.emailtemplate.seller_contact_email')
            ],
            'sellerWebsiteURL' => [
                'type'  => 'string',
                'label' => $this->translator->trans('oro.commerce.emailtemplate.seller_website_url')
            ],
            'sellerTaxID' => [
                'type'  => 'string',
                'label' => $this->translator->trans('oro.commerce.emailtemplate.seller_tax_id')
            ]
        ];
    }

    #[\Override]
    public function getVariableValues(): array
    {
        return [
            'sellerCompanyName' =>
                $this->configManager->get(Configuration::getConfigKeyByName(Configuration::COMPANY_NAME)),
            'sellerBusinessAddress' =>
                $this->configManager->get(Configuration::getConfigKeyByName(Configuration::BUSINESS_ADDRESS)),
            'sellerPhoneNumber' =>
                $this->configManager->get(Configuration::getConfigKeyByName(Configuration::PHONE_NUMBER)),
            'sellerContactEmail' =>
                $this->configManager->get(Configuration::getConfigKeyByName(Configuration::CONTACT_EMAIL)),
            'sellerWebsiteURL' =>
                $this->configManager->get(Configuration::getConfigKeyByName(Configuration::WEBSITE)),
            'sellerTaxID' =>
                $this->configManager->get(Configuration::getConfigKeyByName(Configuration::TAX_ID))
        ];
    }
}

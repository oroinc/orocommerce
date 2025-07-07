<?php

namespace Oro\Bundle\CommerceBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Twig\Sandbox\SystemVariablesProviderInterface;
use Symfony\Component\String\UnicodeString;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides seller-related system variables for email templates.
 */
class SellerInfoVariablesProvider implements SystemVariablesProviderInterface
{
    private ?SellerInfoProvider $sellerInfoProvider = null;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ConfigManager $configManager,
    ) {
    }

    public function setSellerInfoProvider(?SellerInfoProvider $sellerInfoProvider): void
    {
        $this->sellerInfoProvider = $sellerInfoProvider;
    }

    /**
     * Ensures BC with the previous implementation where the {@see SellerInfoProvider} is not set via constructor.
     */
    private function getSellerInfoProvider(): SellerInfoProvider
    {
        $this->sellerInfoProvider ??= new SellerInfoProvider($this->configManager);

        return $this->sellerInfoProvider;
    }

    #[\Override]
    public function getVariableDefinitions(): array
    {
        $sellerInfoKeys = array_keys($this->getSellerInfoProvider()->getSellerInfo());
        $variableDefinitions = [];

        foreach ($sellerInfoKeys as $key) {
            $snakeCaseKey = (new UnicodeString($key))->snake()->toString();
            $variableDefinitions[$key] = [
                'type'  => 'string',
                'label' => $this->translator->trans('oro.commerce.emailtemplate.' . $snakeCaseKey)
            ];
        }

        return $variableDefinitions;
    }

    #[\Override]
    public function getVariableValues(): array
    {
        return $this->getSellerInfoProvider()->getSellerInfo();
    }
}

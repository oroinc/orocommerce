<?php

namespace Oro\Bundle\CommerceBundle\Provider;

use Oro\Bundle\EntityBundle\Twig\Sandbox\SystemVariablesProviderInterface;
use Symfony\Component\String\UnicodeString;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides seller-related system variables for email templates.
 */
class SellerInfoVariablesProvider implements SystemVariablesProviderInterface
{
    public function __construct(
        private readonly SellerInfoProvider $sellerInfoProvider,
        private readonly TranslatorInterface $translator
    ) {
    }

    #[\Override]
    public function getVariableDefinitions(): array
    {
        $sellerInfoKeys = array_keys($this->sellerInfoProvider->getSellerInfo());
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
        return $this->sellerInfoProvider->getSellerInfo();
    }
}

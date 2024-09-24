<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Oro\Bundle\EntityBundle\Twig\Sandbox\SystemVariablesProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides the following system variables for email templates:
 * * contactInfo
 */
class EmailTemplateSystemVariablesProvider implements SystemVariablesProviderInterface
{
    private ContactInfoProviderInterface $contactInfoProvider;

    private TranslatorInterface $translator;

    public function __construct(
        ContactInfoProviderInterface $contactInfoProvider,
        TranslatorInterface $translator,
    ) {
        $this->contactInfoProvider = $contactInfoProvider;
        $this->translator = $translator;
    }

    #[\Override]
    public function getVariableDefinitions(): array
    {
        return [
            'contactInfo' => [
                'type' => 'array',
                'label' => $this->translator->trans('oro.sale.emailtemplate.contact_info'),
            ],
        ];
    }

    #[\Override]
    public function getVariableValues(): array
    {
        return [
            'contactInfo' => $this->contactInfoProvider->getContactInfo()->all(),
        ];
    }
}

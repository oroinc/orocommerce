<?php

namespace Oro\Bundle\CMSBundle\ContentWidget;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Twig\Environment;

/**
 * Abstract class for the content widget types.
 */
abstract class AbstractContentWidgetType implements ContentWidgetTypeInterface
{
    #[\Override]
    public function getBackOfficeViewSubBlocks(ContentWidget $contentWidget, Environment $twig): array
    {
        $additionalInformationBlock = $this->getAdditionalInformationBlock($contentWidget, $twig);
        if (!$additionalInformationBlock) {
            return [];
        }

        return [
            [
                'title' => 'oro.cms.contentwidget.sections.additional_information.label',
                'subblocks' => [
                    [
                        'data' => [
                            $additionalInformationBlock,
                        ]
                    ],
                ]
            ],
        ];
    }

    #[\Override]
    public function getSettingsForm(ContentWidget $contentWidget, FormFactoryInterface $formFactory): ?FormInterface
    {
        return null;
    }

    protected function getAdditionalInformationBlock(ContentWidget $contentWidget, Environment $twig): string
    {
        return '';
    }

    #[\Override]
    public function getWidgetData(ContentWidget $contentWidget): array
    {
        return $contentWidget->getSettings();
    }

    #[\Override]
    public function isInline(): bool
    {
        return false;
    }
}

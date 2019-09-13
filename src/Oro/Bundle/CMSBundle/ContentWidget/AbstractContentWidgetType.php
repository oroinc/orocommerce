<?php

namespace Oro\Bundle\CMSBundle\ContentWidget;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Twig\Environment;

/**
 * Abstract class for the content widget types.
 */
abstract class AbstractContentWidgetType implements ContentWidgetTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBackOfficeViewSubBlocks(ContentWidget $contentWidget, Environment $twig): array
    {
        return [
            [
                'title' => 'oro.cms.contentwidget.sections.additional_information.label',
                'subblocks' => [
                    [
                        'data' => [
                            $this->getAdditionalInformationBlock($contentWidget, $twig),
                        ]
                    ],
                ]
            ],
        ];
    }

    /**
     * @param ContentWidget $contentWidget
     * @param Environment $twig
     * @return string
     */
    abstract protected function getAdditionalInformationBlock(ContentWidget $contentWidget, Environment $twig): string;

    /**
     * {@inheritdoc}
     */
    public function getWidgetData(ContentWidget $contentWidget): array
    {
        return $contentWidget->getSettings();
    }
}

<?php

namespace Oro\Bundle\CMSBundle\EventListener\WebsiteSearchTerm\ContentBlock;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds content block title to search terms datagrid in backoffice.
 */
class AddContentBlockToSearchTermsDatagridListener
{
    private UrlGeneratorInterface $urlGenerator;

    private TranslatorInterface $translator;

    private HtmlTagHelper $htmlTagHelper;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        TranslatorInterface $translator,
        HtmlTagHelper $htmlTagHelper
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->htmlTagHelper = $htmlTagHelper;
    }

    public function onBuildBefore(BuildBefore $event): void
    {
        $config = $event->getConfig();

        $query = $config->getOrmQuery();
        $query
            ->addLeftJoin($query->getRootAlias() . '.contentBlock', 'contentBlock')
            ->addSelect('contentBlock.id as contentBlockId')
            ->addLeftJoin('contentBlock.titles', 'contentBlockTitle')
            ->addSelect('contentBlockTitle.string as contentBlockDefaultTitle')
            ->addAndWhere('contentBlockTitle.localization IS NULL');
    }

    public function onResultAfter(OrmResultAfter $event): void
    {
        foreach ($event->getRecords() as $result) {
            if ($result->getValue('actionType') !== 'modify') {
                continue;
            }

            $contentBlockId = $result->getValue('contentBlockId');
            $contentBlockTitle = $result->getValue('contentBlockDefaultTitle');
            if ($contentBlockId === null || $contentBlockTitle === null) {
                continue;
            }

            $actionDetails = $result->getValue('actionDetails');
            $contentBlockUrl = $this->urlGenerator->generate('oro_cms_content_block_view', ['id' => $contentBlockId]);

            $result->setValue(
                'actionDetails',
                $this->translator->trans(
                    'oro.websitesearchterm.searchterm.grid.action_details.additional_content_block',
                    [
                        '{{ main_action_details }}' => $actionDetails,
                        '{{ content_block_title }}' => $this->htmlTagHelper->escape($contentBlockTitle),
                        '{{ content_block_url }}' => htmlspecialchars($contentBlockUrl, \ENT_QUOTES, 'UTF-8'),
                    ]
                ),
            );
        }
    }
}

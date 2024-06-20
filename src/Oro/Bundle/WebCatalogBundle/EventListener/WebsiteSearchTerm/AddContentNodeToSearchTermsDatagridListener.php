<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener\WebsiteSearchTerm;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds content node redirect action type to search terms datagrid in backoffice.
 */
class AddContentNodeToSearchTermsDatagridListener
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
            ->addLeftJoin($query->getRootAlias() . '.redirectContentNode', 'contentNode')
            ->addSelect('contentNode.id as contentNodeId')
            ->addLeftJoin('contentNode.titles', 'contentNodeTitle')
            ->addSelect('contentNodeTitle.string as contentNodeDefaultTitle')
            ->addAndWhere('contentNodeTitle.localization IS NULL');
    }

    public function onResultAfter(OrmResultAfter $event): void
    {
        foreach ($event->getRecords() as $result) {
            if ($result->getValue('actionType') !== 'redirect'
                || $result->getValue('redirectActionType') !== 'content_node') {
                continue;
            }

            $contentNodeId = $result->getValue('contentNodeId');
            $contentNodeTitle = $result->getValue('contentNodeDefaultTitle');
            if ($contentNodeId === null || $contentNodeTitle === null) {
                continue;
            }

            $contentNodeUrl = $this->urlGenerator->generate('oro_content_node_update', ['id' => $contentNodeId]);

            $result->setValue(
                'actionDetails',
                $this->translator->trans(
                    'oro.websitesearchterm.searchterm.grid.action_details.redirect_content_node',
                    [
                        '{{ content_node_url }}' => htmlspecialchars($contentNodeUrl, \ENT_QUOTES, 'UTF-8'),
                        '{{ content_node_title }}' => $this->htmlTagHelper->escape($contentNodeTitle),
                    ]
                ),
            );
        }
    }
}

<?php

namespace Oro\Bundle\CMSBundle\EventListener\WebsiteSearchTerm\Page;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds page to search terms datagrid in backoffice.
 */
class AddPageToSearchTermsDatagridListener
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
            ->addLeftJoin($query->getRootAlias() . '.redirectCmsPage', 'cmsPage')
            ->addSelect('cmsPage.id as pageId')
            ->addLeftJoin('cmsPage.titles', 'pageTitle')
            ->addSelect('pageTitle.string as pageDefaultTitle')
            ->addAndWhere('pageTitle.localization IS NULL');
    }

    public function onResultAfter(OrmResultAfter $event): void
    {
        foreach ($event->getRecords() as $result) {
            if ($result->getValue('actionType') !== 'redirect'
                || $result->getValue('redirectActionType') !== 'cms_page') {
                continue;
            }

            $pageId = $result->getValue('pageId');
            $pageTitle = $result->getValue('pageDefaultTitle');
            if ($pageId === null || $pageTitle === null) {
                continue;
            }

            $pageUrl = $this->urlGenerator->generate('oro_cms_page_view', ['id' => $pageId]);

            $result->setValue(
                'actionDetails',
                $this->translator->trans(
                    'oro.websitesearchterm.searchterm.grid.action_details.redirect_cms_page',
                    [
                        '{{ cms_page_url }}' => htmlspecialchars($pageUrl, \ENT_QUOTES, 'UTF-8'),
                        '{{ cms_page_title }}' => $this->htmlTagHelper->escape($pageTitle),
                    ]
                ),
            );
        }
    }
}

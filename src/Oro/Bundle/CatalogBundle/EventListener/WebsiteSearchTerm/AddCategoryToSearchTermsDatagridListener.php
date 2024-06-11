<?php

namespace Oro\Bundle\CatalogBundle\EventListener\WebsiteSearchTerm;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds category redirect action type to search terms datagrid in backoffice.
 */
class AddCategoryToSearchTermsDatagridListener
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
            ->addLeftJoin($query->getRootAlias() . '.redirectCategory', 'category')
            ->addSelect('category.id as categoryId')
            ->addLeftJoin('category.titles', 'categoryTitle', 'WITH', 'categoryTitle.localization IS NULL')
            ->addSelect('categoryTitle.string as categoryDefaultTitle');
    }

    public function onResultAfter(OrmResultAfter $event): void
    {
        foreach ($event->getRecords() as $result) {
            if ($result->getValue('actionType') !== 'redirect'
                || $result->getValue('redirectActionType') !== 'category') {
                continue;
            }

            $categoryId = $result->getValue('categoryId');
            $categoryTitle = $result->getValue('categoryDefaultTitle');
            if ($categoryId === null || $categoryTitle === null) {
                continue;
            }

            $categoryUrl = $this->urlGenerator->generate('oro_catalog_category_update', ['id' => $categoryId]);

            $result->setValue(
                'actionDetails',
                $this->translator->trans(
                    'oro.websitesearchterm.searchterm.grid.action_details.redirect_category',
                    [
                        '{{ category_url }}' => htmlspecialchars($categoryUrl, \ENT_QUOTES, 'UTF-8'),
                        '{{ category_title }}' => $this->htmlTagHelper->escape($categoryTitle),
                    ]
                ),
            );
        }
    }
}

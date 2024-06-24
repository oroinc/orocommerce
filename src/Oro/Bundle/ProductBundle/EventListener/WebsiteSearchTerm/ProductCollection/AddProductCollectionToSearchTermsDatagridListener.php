<?php

namespace Oro\Bundle\ProductBundle\EventListener\WebsiteSearchTerm\ProductCollection;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds product collection action details to search terms datagrid in backoffice.
 */
class AddProductCollectionToSearchTermsDatagridListener
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
            ->addLeftJoin($query->getRootAlias() . '.productCollectionSegment', 'productCollection')
            ->addSelect('productCollection.id as productCollectionId')
            ->addSelect('productCollection.name as productCollectionName');
    }

    public function onResultAfter(OrmResultAfter $event): void
    {
        foreach ($event->getRecords() as $result) {
            if ($result->getValue('modifyActionType') !== 'product_collection') {
                continue;
            }

            $productCollectionId = $result->getValue('productCollectionId');
            $productCollectionName = $result->getValue('productCollectionName');
            if ($productCollectionId === null || $productCollectionName === null) {
                continue;
            }

            $productCollectionUrl = $this->urlGenerator->generate('oro_segment_view', ['id' => $productCollectionId]);

            $result->setValue(
                'actionDetails',
                $this->translator->trans(
                    'oro.websitesearchterm.searchterm.grid.action_details.product_collection',
                    [
                        '{{ product_collection_url }}' => htmlspecialchars($productCollectionUrl, \ENT_QUOTES, 'UTF-8'),
                        '{{ product_collection_name }}' => $this->htmlTagHelper->escape($productCollectionName),
                    ]
                ),
            );
        }
    }
}

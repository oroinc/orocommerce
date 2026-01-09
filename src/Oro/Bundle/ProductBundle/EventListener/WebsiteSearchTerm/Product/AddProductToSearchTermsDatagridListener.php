<?php

namespace Oro\Bundle\ProductBundle\EventListener\WebsiteSearchTerm\Product;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds product redirect action type to search terms datagrid in backoffice.
 */
class AddProductToSearchTermsDatagridListener
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
            ->addLeftJoin($query->getRootAlias() . '.redirectProduct', 'product')
            ->addSelect('product.id as productId')
            ->addLeftJoin('product.names', 'productName')
            ->addSelect('productName.string as productDefaultName')
            ->addAndWhere('productName.localization IS NULL');
    }

    public function onResultAfter(OrmResultAfter $event): void
    {
        foreach ($event->getRecords() as $result) {
            if (
                $result->getValue('actionType') !== 'redirect'
                || $result->getValue('redirectActionType') !== 'product'
            ) {
                continue;
            }

            $productId = $result->getValue('productId');
            $productName = $result->getValue('productDefaultName');
            if ($productId === null || $productName === null) {
                continue;
            }

            $productUrl = $this->urlGenerator->generate('oro_product_view', ['id' => $productId]);

            $result->setValue(
                'actionDetails',
                $this->translator->trans(
                    'oro.websitesearchterm.searchterm.grid.action_details.redirect_product',
                    [
                        '{{ product_url }}' => htmlspecialchars($productUrl, \ENT_QUOTES, 'UTF-8'),
                        '{{ product_name }}' => $this->htmlTagHelper->escape($productName),
                    ]
                ),
            );
        }
    }
}

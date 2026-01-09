<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\NavigationBundle\Provider\RouteTitleProvider;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds system page redirect action type to search terms datagrid in backoffice.
 */
class AddSystemPageToSearchTermsDatagridListener
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
        private readonly RouteTitleProvider $routeTitleProvider,
        private readonly HtmlTagHelper $htmlTagHelper
    ) {
    }

    public function onResultAfter(OrmResultAfter $event): void
    {
        foreach ($event->getRecords() as $result) {
            if (
                $result->getValue('actionType') !== 'redirect'
                || $result->getValue('redirectActionType') !== 'system_page'
                || !$result->getValue('redirectSystemPage')
            ) {
                continue;
            }

            $systemPageRoute = (string)$result->getValue('redirectSystemPage');
            $systemPageTitle = $this->routeTitleProvider->getTitle($systemPageRoute, 'frontend_menu');
            $systemPageUrl = $this->urlGenerator->generate($systemPageRoute);

            $result->setValue(
                'actionDetails',
                $this->translator->trans(
                    'oro.websitesearchterm.searchterm.grid.action_details.redirect_system_page',
                    [
                        '{{ system_page_url }}' => htmlspecialchars($systemPageUrl, \ENT_QUOTES, 'UTF-8'),
                        '{{ system_page_title }}' => $this->htmlTagHelper->escape($systemPageTitle),
                    ]
                ),
            );
        }
    }
}

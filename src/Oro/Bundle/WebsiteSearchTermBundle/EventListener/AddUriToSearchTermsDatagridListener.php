<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\String\UnicodeString;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds URI redirect action type to search terms datagrid in backoffice.
 */
class AddUriToSearchTermsDatagridListener
{
    private TranslatorInterface $translator;

    private HtmlTagHelper $htmlTagHelper;

    public function __construct(TranslatorInterface $translator, HtmlTagHelper $htmlTagHelper)
    {
        $this->translator = $translator;
        $this->htmlTagHelper = $htmlTagHelper;
    }

    public function onResultAfter(OrmResultAfter $event): void
    {
        foreach ($event->getRecords() as $result) {
            if (
                $result->getValue('actionType') === 'redirect'
                && $result->getValue('redirectActionType') === 'uri'
                && $result->getValue('redirectUri') !== null
            ) {
                $redirectUri = $result->getValue('redirectUri');
                $truncatedRedirectUri = (string)(new UnicodeString($redirectUri))->truncate(50, '...');
                $title = htmlspecialchars($truncatedRedirectUri, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
                $url = htmlspecialchars($redirectUri, \ENT_QUOTES, 'UTF-8');
                $link = $this->htmlTagHelper->sanitize(sprintf('<a href="%s" target="_blank">%s</a>', $url, $title));

                $result->setValue(
                    'actionDetails',
                    $this->translator->trans(
                        'oro.websitesearchterm.searchterm.grid.action_details.redirect_uri',
                        [
                            '{{ url }}' => $url,
                            '{{ title }}' => $title,
                            '{{ link }}' => $link,
                        ]
                    ),
                );
            }
        }
    }
}

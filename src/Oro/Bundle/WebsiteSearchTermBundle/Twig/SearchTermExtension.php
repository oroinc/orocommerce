<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\Twig;

use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Bundle\WebsiteSearchTermBundle\Formatter\SearchTermPhrasesFormatter;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides Twig filters for formatting {@see SearchTerm}:
 *   - oro_format_search_term_phrases
 */
class SearchTermExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFilters()
    {
        return [
            new TwigFilter('oro_format_search_term_phrases', [$this, 'formatPhrases']),
        ];
    }

    /**
     * Formats string of phrases to an array.
     *
     * Example input value: 'foo, bar'.
     * Example formatted value:
     *  [
     *      'foo',
     *      'bar',
     *  ]
     */
    public function formatPhrases(string $value, ?string $joinWith = null): array|string
    {
        $phrases = $this->getSearchTermPhrasesFormatter()->formatPhrasesToArray($value);
        if ($joinWith !== null) {
            return implode($joinWith, $phrases);
        }

        return $phrases;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            SearchTermPhrasesFormatter::class
        ];
    }

    private function getSearchTermPhrasesFormatter(): SearchTermPhrasesFormatter
    {
        return $this->container->get(SearchTermPhrasesFormatter::class);
    }
}

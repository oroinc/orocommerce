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
    private ContainerInterface $container;
    private ?SearchTermPhrasesFormatter $phrasesFormatter = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

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
     * @param string $value
     * @param string|null $joinWith
     *
     * @return array|string
     */
    public function formatPhrases(string $value, ?string $joinWith = null): array|string
    {
        $phrases = $this->getSearchTermPhrasesFormatter()->formatPhrasesToArray($value);
        if ($joinWith !== null) {
            return implode($joinWith, $phrases);
        }

        return $phrases;
    }

    public static function getSubscribedServices(): array
    {
        return [
            'oro_website_search_term.formatter.search_term_phrases_formatter' => SearchTermPhrasesFormatter::class,
        ];
    }

    private function getSearchTermPhrasesFormatter(): SearchTermPhrasesFormatter
    {
        if (null === $this->phrasesFormatter) {
            $this->phrasesFormatter = $this->container->get(
                'oro_website_search_term.formatter.search_term_phrases_formatter'
            );
        }

        return $this->phrasesFormatter;
    }
}

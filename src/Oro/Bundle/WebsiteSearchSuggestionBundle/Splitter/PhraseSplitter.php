<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Splitter;

/**
 * Split the phrases into word combinations.
 * For example the results for the phrase: Client, Credit Card.
 * 1. client
 * 2. credit
 * 3. card
 * 4. client credit
 * 5. client credit card
 * 6. credit card
 */
class PhraseSplitter
{
    public function split(string $phrase): array
    {
        $words = $this->sanitize($phrase);
        $wordsCount = count($words);
        $suggestions = [];
        for ($i = 0; $i < $wordsCount; $i++) {
            $suggestion = trim($words[$i]);
            $suggestions[$suggestion] = $suggestion;
            for ($j = $i + 1; $j < $wordsCount; $j++) {
                $suggestion = trim($words[$i] . ' ' . $words[$j]);
                $suggestions[$suggestion] = $suggestion;
                for ($l = $j + 1; $l < $wordsCount; $l++) {
                    $suggestion = trim($words[$i] . ' ' . $words[$j] . ' ' . $words[$l]);
                    $suggestions[$suggestion] = $suggestion;
                    for ($k = $l + 1; $k < $wordsCount; $k++) {
                        $suggestion = trim($words[$i] . ' ' . $words[$j] . ' ' . $words[$l] . ' ' . $words[$k]);
                        $suggestions[$suggestion] = $suggestion;
                    }
                }
            }
        }
        return array_values($suggestions);
    }

    protected function sanitize(string $phrase): array
    {
        $symbols = $this->getSpecialSymbols();
        return explode(' ', mb_strtolower(str_replace($symbols, '', $phrase)));
    }

    protected function getSpecialSymbols(): array
    {
        return [
            '’', ',', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '+', '=', '~',
            '`', '[', '{', ']', '}', '\\', '|', ';', ':', "'", '"', '<', '.', '>', '/', '?',
        ];
    }
}

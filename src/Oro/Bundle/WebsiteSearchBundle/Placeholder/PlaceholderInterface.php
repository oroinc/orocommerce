<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

/**
 * Defines the contract for website search placeholders that enable dynamic field name resolution.
 *
 * Placeholders are tokens (e.g., `WEBSITE_ID`, `LOCALIZATION_ID`, `CURRENCY`) embedded in search field names and
 * index aliases that are replaced with actual values at runtime. This mechanism enables the website search system
 * to support multi-website, multi-localization, and multi-currency scenarios by creating separate index fields
 * for each combination of these dimensions. Implementations must provide the placeholder name and logic
 * for replacing it with default or context-specific values.
 */
interface PlaceholderInterface
{
    /**
     * Return placeholder name
     * ex.: WEBSITE_ID
     *
     * @return string
     */
    public function getPlaceholder();

    /**
     * Returns string with replaced placeholder key on needed value
     *
     * @param string $string
     * @return string
     */
    public function replaceDefault($string);

    /**
     * Returns string with replaced placeholder key on needed value
     *
     * @param string $string
     * @param array $values
     * @return string
     */
    public function replace($string, array $values);
}

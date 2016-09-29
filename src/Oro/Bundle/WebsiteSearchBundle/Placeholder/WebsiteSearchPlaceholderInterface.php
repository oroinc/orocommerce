<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

interface WebsiteSearchPlaceholderInterface
{
    /**
     * Return placeholder name
     * ex.: WEBSITE_ID
     *
     * @return string
     */
    public function getPlaceholder();

    /**
     * Return value of the placeholder
     *
     * @return string
     */
    public function getValue();

    /**
     * Returns string with replaced placeholder key on needed value
     *
     * @param string $string
     * @param string $replaceValue
     * @return string
     */
    public function replace($string, $replaceValue);
}

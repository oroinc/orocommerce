<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

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

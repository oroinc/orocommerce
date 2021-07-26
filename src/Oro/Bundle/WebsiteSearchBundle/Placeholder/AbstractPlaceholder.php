<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

/**
 * Abstract implementation of search placeholder.
 */
abstract class AbstractPlaceholder implements PlaceholderInterface
{
    /**
     * {@inheritdoc}
     */
    public function replaceDefault($string)
    {
        if (str_contains($string, $this->getPlaceholder())) {
            return $this->replaceValue($string, $this->getDefaultValue());
        }

        return $string;
    }

    /**
     * {@inheritdoc}
     */
    public function replace($string, array $values)
    {
        if (!\array_key_exists($this->getPlaceholder(), $values)) {
            return $string;
        }

        return $this->replaceValue($string, $values[$this->getPlaceholder()]);
    }

    /**
     * @param string $string
     * @param string $value
     * @return string
     */
    protected function replaceValue($string, $value)
    {
        return str_replace($this->getPlaceholder(), $value, $string);
    }

    /**
     * @return string
     */
    abstract public function getDefaultValue();
}

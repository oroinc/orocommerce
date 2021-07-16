<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

/**
 * Replaces placeholders at with an appropriate values using all registered placeholders
 */
class PlaceholderDecorator implements PlaceholderInterface
{
    const DEFAULT_PLACEHOLDER_VALUE = '.+?';

    /**
     * @var PlaceholderRegistry
     */
    protected $placeholderRegistry;

    /**
     * @var array
     */
    protected $placeholderTokenCache;

    public function __construct(PlaceholderRegistry $registry)
    {
        $this->placeholderRegistry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function replace($string, array $values)
    {
        if (empty($this->placeholderTokenCache)) {
            $this->initPlaceholderTokenCache();
        }

        if (empty($this->placeholderTokenCache)) {
            return (string)$string;
        }

        $pattern = implode('|', $this->placeholderTokenCache);

        if (!preg_match_all('/' . $pattern . '/', (string)$string, $matches)) {
            return (string)$string;
        }

        foreach ($matches[0] as $placeholder) {
            $placeholder = $this->placeholderRegistry->getPlaceholder($placeholder);
            $string = $placeholder->replace((string)$string, $values);
        }

        return $string;
    }

    /**
     * {@inheritdoc}
     */
    public function replaceDefault($string)
    {
        foreach ($this->placeholderRegistry->getPlaceholders() as $placeholder) {
            $string = $placeholder->replaceDefault($string);
        }

        return $string;
    }

    /** {@inheritdoc} */
    public function getPlaceholder()
    {
        throw new \BadMethodCallException('Please use PlaceholderRegistry to get placeholder');
    }

    protected function initPlaceholderTokenCache()
    {
        $placeholders = $this->placeholderRegistry->getPlaceholders();

        if (empty($placeholders)) {
            return;
        }

        foreach ($placeholders as $placeholder) {
            $this->placeholderTokenCache[] = '(' . $placeholder->getPlaceholder() . ')';
        }
    }
}

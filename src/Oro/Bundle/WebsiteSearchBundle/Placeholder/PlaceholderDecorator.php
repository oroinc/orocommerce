<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

class PlaceholderDecorator implements PlaceholderInterface
{
    /**
     * @var PlaceholderRegistry
     */
    private $placeholderRegistry;

    /**
     * @param PlaceholderRegistry $registry
     */
    public function __construct(PlaceholderRegistry $registry)
    {
        $this->placeholderRegistry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function replace($string, array $values)
    {
        foreach ($this->placeholderRegistry->getPlaceholders() as $placeholder) {
            $string = $placeholder->replace($string, $values);
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
}

<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

class WebsiteSearchPlaceholderRegistry
{
    /**
     * @var array
     */
    private $placeholders = [];

    /**
     * @param WebsiteSearchPlaceholderInterface $placeholder
     * @return array
     */
    public function addPlaceholder(WebsiteSearchPlaceholderInterface $placeholder)
    {
        $this->placeholders[$placeholder->getPlaceholder()] = $placeholder->getValue();
    }

    /**
     * @param string $placeholderName
     * @return string
     */
    public function getPlaceholderValue($placeholderName)
    {
        if (!isset($this->placeholders[$placeholderName])) {
            throw new \InvalidArgumentException('Placeholder ' . $placeholderName . ' does not exist.');
        }

        return $this->placeholders[$placeholderName];
    }

    /**
     * @return array
     */
    public function getPlaceholders()
    {
        return $this->placeholders;
    }
}

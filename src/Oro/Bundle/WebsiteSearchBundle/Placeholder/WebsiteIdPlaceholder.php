<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

class WebsiteIdPlaceholder implements WebsiteSearchPlaceholderInterface
{
    const NAME = 'WEBSITE_ID';

    /**
     * @var WebsiteManager
     */
    private $websiteManager;

    /**
     * @param WebsiteManager $websiteManager
     */
    public function __construct(WebsiteManager $websiteManager)
    {
        $this->websiteManager = $websiteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getPlaceholder()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        $website = $this->websiteManager->getCurrentWebsite();

        if (!$website) {
            throw new \RuntimeException('Current website is not defined.');
        }

        return (string)$website->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function replace($string, $replaceValue)
    {
        return str_replace(self::NAME, $replaceValue, $string);
    }
}

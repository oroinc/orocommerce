<?php

namespace Oro\Bundle\WebsiteBundle\Twig;

use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

class OroWebsiteExtension extends \Twig_Extension
{
    const NAME = 'oro_b2b_website_extension';

    /** @var WebsiteManager */
    protected $websiteManager;

    /**
     * Constructor
     *
     * @param WebsiteManager $websiteManager
     */
    public function __construct(WebsiteManager $websiteManager)
    {
        $this->websiteManager = $websiteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_website_get_current_website', [$this->websiteManager, 'getCurrentWebsite'])
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}

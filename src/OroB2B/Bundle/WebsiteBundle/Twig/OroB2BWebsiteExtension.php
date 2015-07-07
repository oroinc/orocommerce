<?php

namespace OroB2B\Bundle\WebsiteBundle\Twig;

use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;

class OroB2BWebsiteExtension extends \Twig_Extension
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
            new \Twig_SimpleFunction('orob2b_website_get_current_website', [$this->websiteManager, 'getCurrentWebsite'])
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

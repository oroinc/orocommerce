<?php

namespace Oro\Bundle\WebsiteBundle\Twig;

use Oro\Bundle\WebsiteBundle\Resolver\WebsiteUrlResolver;

class WebsitePathExtension extends \Twig_Extension
{
    const NAME = 'orob2b_website_path';
    
    /**
     * @var WebsiteUrlResolver
     */
    protected $websiteUrlResolver;

    /**
     * @param WebsiteUrlResolver $websiteUrlResolver
     */
    public function __construct(WebsiteUrlResolver $websiteUrlResolver)
    {
        $this->websiteUrlResolver = $websiteUrlResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('website_path', [$this->websiteUrlResolver, 'getWebsitePath']),
            new \Twig_SimpleFunction('website_secure_path', [$this->websiteUrlResolver, 'getWebsiteSecurePath'])
        ];
    }
}

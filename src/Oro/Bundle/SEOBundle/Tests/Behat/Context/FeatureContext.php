<?php

namespace Oro\Bundle\SEOBundle\Tests\Behat\Context;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * @Then /^Page meta (?P<metaName>(keywords|description|title)) equals "(?P<content>[^"]+)"$/
     *
     * @param string $metaName
     * @param string $content
     */
    public function assertMetaInfo($metaName, $content)
    {
        $page = $this->getSession()->getPage();
        $metaElement = $page->find('css', sprintf('meta[name="%s"]', $metaName));

        static::assertNotNull($metaElement, sprintf('Meta %s element not found', $metaName));

        static::assertEquals(
            $content,
            $metaElement->getAttribute('content'),
            sprintf('Meta %s doesn\`t equal to "%s"', $metaName, $content)
        );
    }

    /**
     * @When /^(?:|I )open the robots.txt url$/
     */
    public function openTheRobotsTxtUrl(): void
    {
        $baseUrl = rtrim($this->getMinkParameter('base_url') ?? '', '/') . '/';
        $domain = parse_url($baseUrl, PHP_URL_HOST);
        if (!$domain) {
            self::fail('Failed to get current domain');
        }

        $this->visitPath('/media/sitemaps/robots.'.$domain.'.txt');
    }
}

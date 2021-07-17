<?php

namespace Oro\Bundle\RedirectBundle\Tests\Behat\Context;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * @Then /^Page should contain Canonical URL with URI "(?P<uri>(?:[^"]|\\")*)"$/
     */
    public function shouldSeeSlugPrototypesFieldFilledWithValue(string $uri)
    {
        $canonicalElement = $this->createElement('Canonical URL');
        static::assertEquals(
            sprintf('%s/%s', $this->getCurrentApplicationUrl(), $uri),
            $canonicalElement->getAttribute('href')
        );
    }

    private function getCurrentApplicationUrl(): string
    {
        $currentUrl = $this->getSession()->getCurrentUrl();
        $port = parse_url($currentUrl, PHP_URL_PORT);
        if ($port) {
            $port = ':' . $port;
        }

        return sprintf(
            '%s://%s%s',
            parse_url($currentUrl, PHP_URL_SCHEME),
            parse_url($currentUrl, PHP_URL_HOST),
            $port
        );
    }
}

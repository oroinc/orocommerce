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
}

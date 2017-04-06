<?php

namespace Oro\Bundle\RedirectBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class SlugPrototypesContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

    /**
     * @Then /^(?:|I )should see URL Slug field filled with "(?P<value>(?:[^"]|\\")*)"$/
     * @param string $value
     */
    public function shouldSeeSlugPrototypesFieldFilledWithValue($value)
    {
        $slugPrototypesField = $this->createElement('SlugPrototypesField');

        self::assertEquals($value, $slugPrototypesField->getValue());
    }

    /**
     * @When /^(?:|I )fill in URL Slug field with "(?P<value>(?:[^"]|\\")*)"$/
     * @param string $value
     */
    public function fillSlugPrototypesFieldFilledWithValue($value)
    {
        $slugPrototypesField = $this->createElement('SlugPrototypesField');

        $slugPrototypesField->setValue($value);
    }
}

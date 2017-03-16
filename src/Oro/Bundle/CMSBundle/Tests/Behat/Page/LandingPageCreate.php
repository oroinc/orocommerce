<?php

namespace Oro\Bundle\CMSBundle\Tests\Behat\Page;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Page;

class LandingPageCreate extends Page
{
    /**
     * {@inheritdoc}
     */
    public function open(array $parameters = [])
    {
        $this->getMainMenu()->openAndClick('Marketing/Landing Pages');
        $this->elementFactory->getPage()->getSession()->getDriver()->waitForAjax();
        $this->elementFactory->getPage()->clickLink('Create Landing Page');
    }
}

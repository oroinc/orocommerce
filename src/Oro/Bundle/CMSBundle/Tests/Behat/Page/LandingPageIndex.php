<?php

namespace Oro\Bundle\CMSBundle\Tests\Behat\Page;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Page;

class LandingPageIndex extends Page
{
    #[\Override]
    public function open(array $parameters = [])
    {
        $this->getMainMenu()->openAndClick('Marketing/Landing Pages');
        $this->elementFactory->getPage()->getSession()->getDriver()->waitForAjax();
    }
}

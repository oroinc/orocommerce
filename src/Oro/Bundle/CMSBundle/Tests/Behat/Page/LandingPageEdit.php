<?php

namespace Oro\Bundle\CMSBundle\Tests\Behat\Page;

use Oro\Bundle\DataGridBundle\Tests\Behat\Element\Grid;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Page;

class LandingPageEdit extends Page
{
    /**
     * {@inheritdoc}
     */
    public function open(array $parameters = [])
    {
        $this->getMainMenu()->openAndClick('Marketing/Landing Pages');

        /** @var Grid $grid */
        $grid = $this->elementFactory->createElement('Grid');
        $grid->getSession()->getDriver()->waitForAjax();
        $grid->clickActionLink($parameters['title'], 'Edit');
    }
}

<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\FormBundle\Tests\Behat\Element\OroForm;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactoryAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\ElementFactoryDictionary;

class CommerceMainContext extends OroFeatureContext implements OroElementFactoryAware
{
    use ElementFactoryDictionary;

    /**
     * Example: Given I login as AmandaRCole@example.org buyer
     *
     * @Given /^I login as (?P<email>\S+) buyer$/
     */
    public function loginAsBuyer($email)
    {
        $this->visitPath('account/user/login');
        $this->waitForAjax();
        /** @var OroForm $form */
        $form = $this->createElement('OroForm');
        $table = new TableNode([
            ['Email Address', $email],
            ['Password', $email]
        ]);
        $form->fill($table);
        $form->pressButton('Sign In');
        $this->waitForAjax();
    }
}

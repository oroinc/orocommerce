<?php

namespace Oro\Bundle\PayPalBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FeatureContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

    /**
     * | Name                      | PayPal               |
     * | Label                     | PayPal               |
     * | Short Label               | PayPal               |
     * | Allowed Credit Card Types | Mastercard           |
     * | Partner                   | qwer1234             |
     * | Vendor                    | qwerty123456         |
     * | User                      | qwer12345            |
     * | Password                  | qwer123423r23r       |
     * | Zero Amount Authorization | true                 |
     * | Payment Action            | Authorize and Charge |
     * @When I fill PayPal integration fields with next data:
     *
     * @param TableNode $table
     *
     * @return Form
     */
    public function iFillPaypalIntegrationFieldsWithNextData(TableNode $table)
    {
        /** @var Form $form */
        $form = $this->createElement('PayPalForm');
        $form->fill($table);

        return $form;
    }

    /**
     * Example: And I fill credit card form with next data:
     * | CreditCardNumber | 5555555555554444 |
     * | Month            | 11               |
     * | Year             | 19               |
     * | CVV              | 123              |
     * @Then I fill credit card form with next data:
     *
     * @param TableNode $table
     *
     * @return Form
     */
    public function fillCreditCardFormWithNextData(TableNode $table)
    {
        /** @var Form $form */
        $form = $this->createElement('PayPalCreditCardForm');
        $form->fill($table);

        return $form;
    }
}

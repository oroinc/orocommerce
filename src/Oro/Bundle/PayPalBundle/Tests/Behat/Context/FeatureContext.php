<?php

namespace Oro\Bundle\PayPalBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Tests\Behat\Mock\PayPal\Payflow\Client\NVPClientMock;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Entity\User;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;
    use UserUtilityTrait;

    /**
     * @Given /^(?:I )?create "(?P<name>(?:[^"]+))" PayPal Payflow integration$/
     * @Given /^(?:I )?create PayPal Payflow integration$/
     * @Given /^(?:I )?create "(?P<name>(?:[^"]+))" PayPal Payflow integration with following settings:$/
     * @Given /^(?:I )?create PayPal Payflow integration with following settings:$/
     */
    public function iCreatePayPalPayflowIntegration(string $name = 'PayPalFlow', ?TableNode $settingsTable = null)
    {
        $this->createPayPalIntegration('paypal_payflow_gateway', $name, $settingsTable);
    }

    /**
     * @Given /^(?:I )?create "(?P<name>(?:[^"]+))" PayPal PaymentsPro integration"$/
     * @Given /^(?:I )?create PayPal PaymentsPro integration$/
     * @Given /^(?:I )?create "(?P<name>(?:[^"]+))" PayPal PaymentsPro integration with following settings:$/
     * @Given /^(?:I )?create PayPal PaymentsPro integration with following settings:$/
     */
    public function iCreatePayPalPaymentsProIntegration(string $name = 'PayPalPro', ?TableNode $settingsTable = null)
    {
        $this->createPayPalIntegration('paypal_payments_pro', $name, $settingsTable);
    }

    private function createPayPalIntegration(string $type, string $name, ?TableNode $settingsTable = null)
    {
        $settings = $this->getIntegrationSettings($type);
        if ($settingsTable !== null) {
            $settings = array_merge($settings, $settingsTable->getRowsHash());
        }

        $transport = $this->createTransport($settings);
        $channel = $this->createChannel($name, $type, $transport);
        $transport->setChannel($channel);

        $entityManager = $this->getAppContainer()->get('oro_entity.doctrine_helper')
            ->getEntityManagerForClass(Channel::class);
        $entityManager->persist($channel);
        $entityManager->flush();
    }

    private function getIntegrationSettings(string $type): array
    {
        $baseSettings = [
            'allowedCreditCardTypes' => ['mastercard'],
            'partner' => 'PayPal',
            'vendor' => 'qwerty123456',
            'user' => 'qwer12345',
            'password' => 'qwer123423r23r',
            'zeroAmountAuthorization' => false,
            'authorizationForRequiredAmount' => false,
            'creditCardPaymentAction' => 'authorize',
            'expressCheckoutName' => 'ExpressPayPal',
            'expressCheckoutLabels' => 'ExpressPayPal',
            'expressCheckoutShortLabels' => 'ExprPPl',
            'expressCheckoutPaymentAction' => 'authorize',
        ];

        $settings = [
            $this->getAppContainer()->getParameter('oro_paypal.method.paypal_payflow_gateway') => array_merge(
                $baseSettings,
                [
                    'creditCardLabels' => 'PayPalFlow',
                    'creditCardShortLabels' => 'PPlFlow',
                ]
            ),
            $this->getAppContainer()->getParameter('oro_paypal.method.paypal_payments_pro') => array_merge(
                $baseSettings,
                [
                    'creditCardLabels' => 'PayPalPro',
                    'creditCardShortLabels' => 'PPlPro',
                ]
            ),
        ];

        self::assertArrayHasKey(
            $type,
            $settings,
            sprintf('Unknown PayPal integration channel type. Supported types: %s', implode(',', array_keys($settings)))
        );

        return $settings[$type];
    }

    private function createTransport(array $settings): PayPalSettings
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $transport = new PayPalSettings();
        foreach ($settings as $key => $value) {
            if ($this->isLocalizedProperty($key)) {
                $value = [(new LocalizedFallbackValue())->setString($value)];
            }

            $propertyAccessor->setValue($transport, $key, $value);
        }

        return $transport;
    }

    protected function createChannel(string $name, string $type, $transport): Channel
    {
        $doctrineHelper = $this->getAppContainer()->get('oro_entity.doctrine_helper');
        $owner = $this->getFirstUser($doctrineHelper->getEntityManagerForClass(User::class));

        $channel = new Channel();
        $channel->setName($name)
            ->setType($type)
            ->setEnabled(true)
            ->setDefaultUserOwner($owner)
            ->setOrganization($owner->getOrganization())
            ->setTransport($transport);

        return $channel;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    private function isLocalizedProperty(string $name)
    {
        return \in_array($name, [
            'creditCardLabels',
            'creditCardShortLabels',
            'expressCheckoutLabels',
            'expressCheckoutShortLabels',
        ]);
    }

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

    /**
     * Example: I should see the following products before pay:
     * | NAME        | DESCRIPTION        |
     * | Item Name 1 | Item Description 1 |
     * @Then /^(?:|I )should see the following products before pay:$/
     */
    public function assertExistsProductDataBeforePay(TableNode $table)
    {
        $lineItems = $this->getAppContainer()
            ->get('oro_paypal.behat.express_payment.cache')
            ->getItem(NVPClientMock::LINE_ITEM_CACHE_KEY);
        if ($lineItems->isHit()) {
            foreach ($table as $row) {
                foreach ($row as $columnName => $rowValue) {
                    self::assertTrue(in_array($rowValue, $lineItems->get(), true));
                }
            }
        }
    }

    /**
     * Example: I should not see the following products before pay:
     * | NAME        | DESCRIPTION        |
     * | Item Name 1 | Item Description 1 |
     * @Then /^(?:|I )should not see the following products before pay:$/
     */
    public function assertNotExistsProductDataBeforePay(TableNode $table)
    {
        $cacheItem = $this->getAppContainer()
            ->get('oro_paypal.behat.express_payment.cache')
            ->getItem(NVPClientMock::LINE_ITEM_CACHE_KEY);
        $lineItems = $cacheItem->isHit() ? $cacheItem->get() : [];
        foreach ($table as $row) {
            foreach ($row as $columnName => $rowValue) {
                self::assertFalse(in_array($rowValue, $lineItems, true));
            }
        }
    }
}

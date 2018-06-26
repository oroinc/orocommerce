<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Entity;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\ParameterBag;

class PayPalSettingsTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(new PayPalSettings(), [
            ['expressCheckoutName', 'some string'],
            ['partner', 'some string'],
            ['vendor', 'some string'],
            ['user', 'some string'],
            ['password', 'some string'],
            ['testMode', false],
            ['debugMode', false],
            ['requireCVVEntry', true],
            ['zeroAmountAuthorization', false],
            ['authorizationForRequiredAmount', false],
            ['useProxy', false],
            ['proxyHost', 'some string'],
            ['proxyPort', 'some string'],
            ['expressCheckoutPaymentAction', 'charge'],
            ['creditCardPaymentAction', 'charge'],
            ['allowedCreditCardTypes', ['visa']],
        ]);
        static::assertPropertyCollections(new PayPalSettings(), [
            ['creditCardLabels', new LocalizedFallbackValue()],
            ['creditCardShortLabels', new LocalizedFallbackValue()],
            ['expressCheckoutLabels', new LocalizedFallbackValue()],
            ['expressCheckoutShortLabels', new LocalizedFallbackValue()],
        ]);
    }

    public function testGetSettingsBag()
    {
        /** @var PayPalSettings $entity */
        $entity = $this->getEntity(
            PayPalSettings::class,
            [
                'expressCheckoutName' => 'some name',
                'partner' => 'some partner',
                'vendor' => 'some vendor',
                'user' => 'some user',
                'password' => 'some password',
                'testMode' => true,
                'debugMode' => true,
                'requireCVVEntry' => false,
                'zeroAmountAuthorization' => true,
                'authorizationForRequiredAmount' => true,
                'useProxy' => true,
                'proxyHost' => 'some host',
                'proxyPort' => 'some proxy',
                'expressCheckoutPaymentAction' => 'charge',
                'creditCardPaymentAction' => 'charge',
                'allowedCreditCardTypes' => ['visa', 'mastercard'],
                'creditCardLabels' => [(new LocalizedFallbackValue())->setString('label')],
                'creditCardShortLabels' => [(new LocalizedFallbackValue())->setString('lbl')],
                'expressCheckoutLabels' => [(new LocalizedFallbackValue())->setString('label')],
                'expressCheckoutShortLabels' => [(new LocalizedFallbackValue())->setString('lbl')],
            ]
        );

        /** @var ParameterBag $result */
        $result = $entity->getSettingsBag();

        static::assertEquals('some name', $result->get('express_checkout_name'));
        static::assertEquals('some partner', $result->get('partner'));
        static::assertEquals('some vendor', $result->get('vendor'));
        static::assertEquals('some user', $result->get('user'));
        static::assertEquals('some password', $result->get('password'));
        static::assertEquals(true, $result->get('test_mode'));
        static::assertEquals(true, $result->get('debug_mode'));
        static::assertEquals(false, $result->get('require_cvv_entry'));
        static::assertEquals(true, $result->get('zero_amount_authorization'));
        static::assertEquals(true, $result->get('authorization_for_required_amount'));
        static::assertEquals('some host', $result->get('proxy_host'));
        static::assertEquals('some proxy', $result->get('proxy_port'));

        static::assertEquals(
            $result->get('allowed_credit_card_types'),
            $entity->getAllowedCreditCardTypes()
        );
        static::assertEquals(
            $result->get('express_checkout_payment_action'),
            $entity->getExpressCheckoutPaymentAction()
        );
        static::assertEquals(
            $result->get('credit_card_payment_action'),
            $entity->getCreditCardPaymentAction()
        );

        static::assertEquals(
            $result->get('credit_card_labels'),
            $entity->getCreditCardLabels()
        );
        static::assertEquals(
            $result->get('credit_card_short_labels'),
            $entity->getCreditCardShortLabels()
        );
        static::assertEquals(
            $result->get('express_checkout_labels'),
            $entity->getExpressCheckoutLabels()
        );
        static::assertEquals(
            $result->get('express_checkout_short_labels'),
            $entity->getExpressCheckoutShortLabels()
        );
    }
}

<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\ExecutionContextInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\PriceListCurrency;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\PriceListCurrencyValidator;

class PriceListCurrencyValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PriceListCurrencyValidator
     */
    protected $validator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager
     */
    protected $configManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LocaleSettings
     */
    protected $localeSettings;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ExecutionContextInterface
     */
    protected $context;

    /**
     * @var string
     */
    protected $locale;

    protected function setUp()
    {
        $this->locale = \Locale::getDefault();
        \Locale::setDefault('en');

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeSettings = $this
            ->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->setMethods(['getCurrency', 'getLocale'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeSettings->expects($this->any())
            ->method('getLocale')
            ->willReturn(\Locale::getDefault());

        $this->context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');

        $this->validator = new PriceListCurrencyValidator($this->configManager, $this->localeSettings);
        $this->validator->initialize($this->context);
    }

    protected function tearDown()
    {
        \Locale::setDefault($this->locale);

        unset($this->validator, $this->configManager, $this->context, $this->localeSettings, $this->locale);
    }

    /**
     * @param PriceList $priceList
     * @param array $configuredCurrencies
     * @param array $invalidCurrencies
     * @param array $constraintConfig
     *
     * @dataProvider validateDataProvider
     */
    public function testValidate(
        PriceList $priceList,
        array $configuredCurrencies,
        array $invalidCurrencies,
        array $constraintConfig = []
    ) {
        $this->configManager->expects($this->any())->method('get')->willReturn($configuredCurrencies);
        $this->localeSettings->expects($this->any())->method('getCurrency')->willReturn('USD');

        if ($invalidCurrencies) {
            $this->context->expects($this->once())->method('addViolationAt')->with(
                $this->isType('string'),
                $this->isType('string'),
                $this->equalTo(['%invalidCurrencies%' => implode(', ', $invalidCurrencies)])
            );
        }

        $this->validator->validate($priceList, new PriceListCurrency($constraintConfig));
    }

    /**
     * @return array
     */
    public function validateDataProvider()
    {
        return [
            'valid currency' => [$this->getPriceList(['EUR']), ['USD', 'EUR'], []],
            'missing in config' => [$this->getPriceList(['UAH']), ['USD', 'EUR'], ['Ukrainian Hryvnia [UAH]']],
            'empty configured and default from locale settings' => [
                $this->getPriceList(['USD', 'UAH']),
                [],
                ['Ukrainian Hryvnia [UAH]']
            ],
            'multiple not in config' => [
                $this->getPriceList(['USD', 'UAH', 'GBP']),
                ['USD', 'EUR'],
                ['Ukrainian Hryvnia [UAH], British Pound Sterling [GBP]']
            ],
            'invalid currency code without label' => [
                $this->getPriceList(['USD', 'EUR', 'UAH', 'AAA']),
                ['USD', 'EUR'],
                ['AAA' => 'AAA'],
                ['useIntl' => true]
            ],
        ];
    }

    /**
     * @param array $currencies
     * @return PriceList
     */
    protected function getPriceList(array $currencies)
    {
        $priceList = new PriceList();
        $priceList->setCurrencies($currencies);

        return $priceList;
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage must be instance of "OroB2B\Bundle\PricingBundle\Entity\PriceList", "stdClass" given
     */
    public function testInvalidArgument()
    {
        $this->validator->validate(new \stdClass(), new PriceListCurrency());
    }
}

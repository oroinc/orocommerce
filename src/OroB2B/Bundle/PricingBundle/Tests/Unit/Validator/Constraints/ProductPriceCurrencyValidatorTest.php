<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\ExecutionContextInterface;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\ProductPriceCurrency;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\ProductPriceCurrencyValidator;

class ProductPriceCurrencyValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductPriceCurrencyValidator
     */
    protected $validator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ExecutionContextInterface
     */
    protected $context;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');

        $this->validator = new ProductPriceCurrencyValidator();
        $this->validator->initialize($this->context);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->validator, $this->context);
    }

    /**
     * @param ProductPrice $productPrice
     * @param string|null $invalidCurrency
     *
     * @dataProvider validateDataProvider
     */
    public function testValidate(ProductPrice $productPrice, $invalidCurrency = null)
    {
        $this->context
            ->expects($invalidCurrency ? $this->once() : $this->never())
            ->method('addViolationAt')
            ->with(
                $this->isType('string'),
                $this->isType('string'),
                $this->equalTo(['%invalidCurrency%' => $invalidCurrency])
            );

        $this->validator->validate($productPrice, new ProductPriceCurrency());
    }

    /**
     * @return array
     */
    public function validateDataProvider()
    {
        return [
            'no price' => [$this->getProductPrice(['USD', 'EUR'])],
            'valid price' => [$this->getProductPrice(['USD', 'EUR'], 'EUR')],
            'invalid price' => [$this->getProductPrice(['USD', 'EUR'], 'UAH'), 'UAH']
        ];
    }

    /**
     * @param array $priceListCurrencies
     * @param string|null $currency
     *
     * @return ProductPrice
     */
    protected function getProductPrice(array $priceListCurrencies, $currency = null)
    {
        $priceList = new PriceList();
        $priceList->setCurrencies($priceListCurrencies);

        $productPrice = new ProductPrice();
        $productPrice->setPriceList($priceList);

        if ($currency) {
            $price = new Price();
            $price->setCurrency($currency);
            $productPrice->setPrice($price);
        }

        return $productPrice;
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage must be instance of "OroB2B\Bundle\PricingBundle\Entity\ProductPrice", "stdClass" given
     */
    public function testInvalidArgument()
    {
        $this->validator->validate(new \stdClass(), new ProductPriceCurrency());
    }
}

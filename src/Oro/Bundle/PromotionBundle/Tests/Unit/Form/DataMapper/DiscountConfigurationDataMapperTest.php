<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\DataMapper;

use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Form\DataMapper\DiscountConfigurationDataMapper;
use Oro\Bundle\PromotionBundle\Form\Type\DiscountOptionsType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\Test\FormInterface;

class DiscountConfigurationDataMapperTest extends \PHPUnit\Framework\TestCase
{
    private const ANY_FIELD = 'anyField';

    /** @var DiscountConfigurationDataMapper */
    private $dataMapper;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->dataMapper = new DiscountConfigurationDataMapper();
    }

    public function testMapDataToFormsWithNullData()
    {
        $amountDiscountValueForm = $this->createMock(FormInterface::class);
        $amountDiscountValueForm->expects($this->never())
            ->method('getConfig');

        /** @var FormInterface[]|\PHPUnit\Framework\MockObject\MockObject[] $forms */
        $forms = new \ArrayIterator([$amountDiscountValueForm]);

        $this->dataMapper->mapDataToForms(null, $forms);
    }

    public function testMapDataToFormsWithInvalidData()
    {
        $data = 'any not acceptable';
        $amountDiscountValueForm = $this->createMock(FormInterface::class);
        /** @var FormInterface[]|\PHPUnit\Framework\MockObject\MockObject[] $forms */
        $forms = new \ArrayIterator([$amountDiscountValueForm]);

        $this->expectException(UnexpectedTypeException::class);
        $this->dataMapper->mapDataToForms($data, $forms);
    }

    public function testMapFormsToDataWithNullData()
    {
        $amountDiscountValueForm = $this->createMock(FormInterface::class);
        $amountDiscountValueForm->expects($this->never())
            ->method('getConfig');

        /** @var FormInterface[]|\PHPUnit\Framework\MockObject\MockObject[] $forms */
        $forms = new \ArrayIterator([$amountDiscountValueForm]);

        $data = null;
        $this->dataMapper->mapFormsToData($forms, $data);
    }

    public function testMapFormsToDataWithInvalidData()
    {
        $data = 'any not acceptable';
        $amountDiscountValueForm = $this->createMock(FormInterface::class);
        /** @var FormInterface[]|\PHPUnit\Framework\MockObject\MockObject[] $forms */
        $forms = new \ArrayIterator([$amountDiscountValueForm]);

        $this->expectException(UnexpectedTypeException::class);
        $this->dataMapper->mapFormsToData($forms, $data);
    }

    public function testMapAmountDiscountDataToForm()
    {
        $data = [AbstractDiscount::DISCOUNT_VALUE => 123, AbstractDiscount::DISCOUNT_CURRENCY => 'USD'];
        $valueBasedOnData = MultiCurrency::create(
            $data[AbstractDiscount::DISCOUNT_VALUE],
            $data[AbstractDiscount::DISCOUNT_CURRENCY]
        );

        $forms = $this->getForms();
        $forms[DiscountOptionsType::AMOUNT_DISCOUNT_VALUE_FIELD]->expects($this->once())
            ->method('setData')
            ->with($valueBasedOnData);

        $this->dataMapper->mapDataToForms($data, $forms);
    }

    public function testMapAmountDiscountFormToData()
    {
        $expectedData = [
            AbstractDiscount::DISCOUNT_VALUE => 123,
            AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
            AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_AMOUNT,
            self::ANY_FIELD => null
        ];
        $valueBasedOnData = MultiCurrency::create(
            $expectedData[AbstractDiscount::DISCOUNT_VALUE],
            $expectedData[AbstractDiscount::DISCOUNT_CURRENCY]
        );

        $forms = $this->getForms();
        $this->addTypeForm($forms);
        $forms[DiscountOptionsType::AMOUNT_DISCOUNT_VALUE_FIELD]->expects($this->any())
            ->method('getData')
            ->willReturn($valueBasedOnData);
        $forms[AbstractDiscount::DISCOUNT_TYPE]->expects($this->any())
            ->method('getData')
            ->willReturn(DiscountInterface::TYPE_AMOUNT);

        $actualData = [];
        $this->dataMapper->mapFormsToData($forms, $actualData);

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapPercentDiscountDataToForm()
    {
        $data = [
            AbstractDiscount::DISCOUNT_VALUE => 123,
            AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_PERCENT
        ];
        $valueBasedOnData = 123;

        $forms = $this->getForms();
        $this->addTypeForm($forms);
        $forms[DiscountOptionsType::PERCENT_DISCOUNT_VALUE_FIELD]->expects($this->once())
            ->method('setData')
            ->with($valueBasedOnData);

        $this->dataMapper->mapDataToForms($data, $forms);
    }

    public function testMapPercentDiscountFormToData()
    {
        $expectedData = [
            AbstractDiscount::DISCOUNT_VALUE => 123,
            self::ANY_FIELD => null,
            AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_PERCENT
        ];
        $valueBasedOnData =123;

        $forms = $this->getForms();
        $this->addTypeForm($forms);
        $forms[DiscountOptionsType::PERCENT_DISCOUNT_VALUE_FIELD]->expects($this->any())
            ->method('getData')
            ->willReturn($valueBasedOnData);
        $forms[AbstractDiscount::DISCOUNT_TYPE]->expects($this->any())
            ->method('getData')
            ->willReturn(DiscountInterface::TYPE_PERCENT);

        $actualData = [];
        $this->dataMapper->mapFormsToData($forms, $actualData);

        $this->assertEquals($expectedData, $actualData);
    }

    public function testMapAnyFieldDataToForm()
    {
        $data = [self::ANY_FIELD => 123];
        $valueBasedOnData = 123;

        $forms = $this->getForms();
        $forms[self::ANY_FIELD]->expects($this->once())
            ->method('setData')
            ->with($valueBasedOnData);

        $this->dataMapper->mapDataToForms($data, $forms);
    }

    public function testMapAnyFieldFormToData()
    {
        $expectedData = [
            self::ANY_FIELD => 123
        ];
        $valueBasedOnData =123;

        $forms = $this->getForms();
        $forms[self::ANY_FIELD]->expects($this->any())
            ->method('getData')
            ->willReturn($valueBasedOnData);

        $actualData = [];
        $this->dataMapper->mapFormsToData($forms, $actualData);

        $this->assertEquals($expectedData, $actualData);
    }

    /**
     * @return FormInterface[]|\PHPUnit\Framework\MockObject\MockObject[]
     */
    private function getForms()
    {
        $config = new FormConfigBuilder(
            DiscountOptionsType::AMOUNT_DISCOUNT_VALUE_FIELD,
            \stdClass::class,
            $this->dispatcher
        );
        $amountDiscountValueForm = $this->getMockBuilder(Form::class)
            ->setConstructorArgs([$config])
            ->onlyMethods(['setData', 'getData'])
            ->getMock();

        $config = new FormConfigBuilder(
            DiscountOptionsType::PERCENT_DISCOUNT_VALUE_FIELD,
            \stdClass::class,
            $this->dispatcher
        );
        $percentDiscountValueForm = $this->getMockBuilder(Form::class)
            ->setConstructorArgs([$config])
            ->onlyMethods(['setData', 'getData'])
            ->getMock();

        $config = new FormConfigBuilder(self::ANY_FIELD, \stdClass::class, $this->dispatcher);
        $anyFieldForm = $this->getMockBuilder(Form::class)
            ->setConstructorArgs([$config])
            ->onlyMethods(['setData', 'getData'])
            ->getMock();

        /** @var FormInterface[]|\PHPUnit\Framework\MockObject\MockObject[] $forms */
        $forms = new \ArrayIterator([
            DiscountOptionsType::AMOUNT_DISCOUNT_VALUE_FIELD => $amountDiscountValueForm,
            DiscountOptionsType::PERCENT_DISCOUNT_VALUE_FIELD => $percentDiscountValueForm,
            self::ANY_FIELD => $anyFieldForm
        ]);

        return $forms;
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject[]|FormInterface[] $forms
     */
    private function addTypeForm(&$forms)
    {
        $config = new FormConfigBuilder(
            AbstractDiscount::DISCOUNT_TYPE,
            \stdClass::class,
            $this->dispatcher
        );
        $discountTypeForm = $this->getMockBuilder(Form::class)
            ->setConstructorArgs([$config])
            ->onlyMethods(['setData', 'getData'])
            ->getMock();
        $forms[AbstractDiscount::DISCOUNT_TYPE] = $discountTypeForm;
    }
}

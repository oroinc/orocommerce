<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Oro\Bundle\PromotionBundle\Provider\DiscountFormTypeProvider;

class DiscountFormTypeProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DiscountFormTypeProvider
     */
    private $discountFormTypeRegistry;

    protected function setUp(): void
    {
        $this->discountFormTypeRegistry = new DiscountFormTypeProvider();
    }

    public function testGetDiscountFormTypeWithExistingDiscountType()
    {
        $formType = 'firstFormType';
        $this->discountFormTypeRegistry->addFormType('firstType', 'firstFormType');

        static::assertEquals(
            $formType,
            $this->discountFormTypeRegistry->getFormType('firstType')
        );
    }

    public function testGetDiscountFormTypeWithNotExistingDiscountType()
    {
        static::assertNull($this->discountFormTypeRegistry->getFormType('firstType'));
    }

    public function testGetDiscountFormTypes()
    {
        $this->discountFormTypeRegistry->addFormType('firstType', 'firstFormType');
        $this->discountFormTypeRegistry->addFormType('secondType', 'secondFormType');

        static::assertEquals(
            [
                'firstType' => 'firstFormType',
                'secondType' => 'secondFormType'
            ],
            $this->discountFormTypeRegistry->getFormTypes()
        );
    }

    public function testGetDefaultFormTypeException()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Default discount type is not provided.');
        $this->discountFormTypeRegistry->getDefaultFormType();
    }
}

<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\ImportExport\Converter;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\DataAccessor\ComplexDataConvertationDataAccessorInterface;
use Oro\Bundle\OrderBundle\ImportExport\Converter\CustomerConverter;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerConverterTest extends TestCase
{
    private ComplexDataConvertationDataAccessorInterface&MockObject $dataAccessor;
    private CustomerConverter $converter;

    #[\Override]
    protected function setUp(): void
    {
        $this->dataAccessor = $this->createMock(ComplexDataConvertationDataAccessorInterface::class);

        $this->converter = new CustomerConverter($this->dataAccessor);
    }

    public function testConvert(): void
    {
        $customer = new Customer();
        ReflectionUtil::setId($customer, 234);
        $customerUser = new CustomerUser();
        ReflectionUtil::setId($customerUser, 123);
        $customerUser->setCustomer($customer);

        $this->dataAccessor->expects(self::once())
            ->method('findEntity')
            ->with(CustomerUser::class, 'id', 123)
            ->willReturn($customerUser);

        $item = [
            'entity' => [
                'relationships' => [
                    'customerUser' => ['data' => ['id' => 123]]
                ]
            ]
        ];
        $expectedItem = [
            'entity' => [
                'relationships' => [
                    'customerUser' => ['data' => ['id' => 123]],
                    'customer' => ['data' => ['type' => 'customers', 'id' => 234]]
                ]
            ]
        ];

        self::assertEquals($expectedItem, $this->converter->convert($item, 'sourceData'));
    }

    public function testConvertWhenCustomerUserDoesNotBelongToAnyCustomer(): void
    {
        $customerUser = new CustomerUser();
        ReflectionUtil::setId($customerUser, 123);

        $this->dataAccessor->expects(self::once())
            ->method('findEntity')
            ->with(CustomerUser::class, 'id', 123)
            ->willReturn($customerUser);

        $item = [
            'entity' => [
                'relationships' => [
                    'customerUser' => ['data' => ['id' => 123]]
                ]
            ]
        ];

        self::assertEquals($item, $this->converter->convert($item, 'sourceData'));
    }

    public function testConvertWhenDataHaveNoCustomerUserData(): void
    {
        $item = [
            'entity' => [
                'relationships' => []
            ]
        ];

        self::assertEquals($item, $this->converter->convert($item, 'sourceData'));
    }
}

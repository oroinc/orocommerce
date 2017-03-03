<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Helper;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Entity\Repository\CustomerTaxCodeRepository;
use Oro\Bundle\TaxBundle\Helper\CustomerTaxCodeImportExportHelper;

class CustomerTaxCodeImportExportHelperTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var  CustomerTaxCodeImportExportHelper */
    private $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var  Customer[] */
    private $customers;

    protected function setUp()
    {
        $this->customers = [
            1 => $this->getEntity(Customer::class, ['id' => 1]),
            2 => $this->getEntity(Customer::class, ['id' => 2]),
        ];

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new CustomerTaxCodeImportExportHelper(
            $this->doctrineHelper
        );
    }

    public function testGetCustomerTaxCodeTest()
    {
        $this->doctrineShouldReturnTags();
        $customerTaxCodes = $this->manager->loadCustomerTaxCode($this->customers);

        foreach ($this->customers as $customer) {
            $this->assertEquals($customer->getId(), $customerTaxCodes[$customer->getId()]->getId());
        }
    }

    /**
     * @dataProvider normalizeCustomerTaxCodeDataProvider
     */
    public function testNormalizeCustomerTaxCode($expectedName, CustomerTaxCode $customerTaxCode)
    {
        $normalizedCustomerTaxCode = $this->manager->normalizeCustomerTaxCode($customerTaxCode);

        $this->assertArrayHasKey('code', $normalizedCustomerTaxCode);
        $this->assertEquals($expectedName, $normalizedCustomerTaxCode['code']);
    }

    /**
     * @return array
     */
    public function normalizeCustomerTaxCodeDataProvider()
    {
        return [
            ['test', $this->getEntity(CustomerTaxCode::class, ['code' => 'test'])],
            [null, $this->getEntity(CustomerTaxCode::class)],
        ];
    }

    private function doctrineShouldReturnTags()
    {
        $map = [];

        foreach ($this->customers as $customer) {
            $customerTaxCode = $this->getEntity(CustomerTaxCode::class, [
                'id' => $customer->getId(),
                'code' => sprintf('code_%s', $customer->getId()),
                'description' => sprintf('description_%s', $customer->getId())
            ]);

            $map[] = [$customer, $customerTaxCode];
        }

        $repository = $this->createMock(CustomerTaxCodeRepository::class);
        $repository->expects($this->exactly(count($this->customers)))->method('findOneByCustomer')
            ->willReturnMap($map);
        $this->doctrineHelper->expects($this->exactly(count($this->customers)))
            ->method('getEntityRepository')
            ->willReturn($repository);
    }
}

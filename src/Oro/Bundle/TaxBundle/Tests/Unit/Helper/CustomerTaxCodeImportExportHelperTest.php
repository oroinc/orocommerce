<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Helper;

use Doctrine\ORM\EntityManager;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Entity\Repository\CustomerTaxCodeRepository;
use Oro\Bundle\TaxBundle\Helper\CustomerTaxCodeImportExportHelper;

class CustomerTaxCodeImportExportHelperTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var CustomerTaxCodeImportExportHelper */
    private $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var  \PHPUnit_Framework_MockObject_MockObject|CustomerTaxCodeRepository */
    private $repository;

    /** @var  \PHPUnit_Framework_MockObject_MockObject|EntityManager */
    private $entityManager;

    /** @var  Customer[] */
    private $customers;

    /** @var CustomerTaxCode[] */
    private $customerTaxCodes;

    protected function setUp()
    {
        $this->createCustomers();
        $this->createCustomersTaxCodes();

        $this->repository = $this->createMock(CustomerTaxCodeRepository::class);
        $this->entityManager = $this->createMock(EntityManager::class);

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($this->repository);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->entityManager);

        $this->manager = new CustomerTaxCodeImportExportHelper(
            $this->doctrineHelper
        );
    }

    public function testGetCustomerTaxCodeTest()
    {
        $this->doctrineShouldReturnTaxCodesByCustomer();
        $customerTaxCodes = $this->manager->loadCustomerTaxCode($this->customers);

        foreach ($this->customers as $customer) {
            $this->assertEquals($customer->getId(), $customerTaxCodes[$customer->getId()]->getId());
        }
    }

    /**
     * @dataProvider normalizeCustomerTaxCodeDataProvider
     * @param string $expectedName
     * @param CustomerTaxCode $customerTaxCode
     */
    public function testNormalizeCustomerTaxCode($expectedName, CustomerTaxCode $customerTaxCode)
    {
        $normalizedCustomerTaxCode = $this->manager->normalizeCustomerTaxCode($customerTaxCode);

        $this->assertArrayHasKey('code', $normalizedCustomerTaxCode);
        $this->assertEquals($expectedName, $normalizedCustomerTaxCode['code']);
    }

    /**
     * @dataProvider denormalizeCustomerTaxCodeDataProvider
     * @param CustomerTaxCode|null $expectedTaxCode
     * @param array $data
     */
    public function testDenormalizeCustomerTaxCode(CustomerTaxCode $expectedTaxCode = null, array $data)
    {
        $this->doctrineShouldReturnTagsByCode();
        $taxCode = $this->manager->denormalizeCustomerTaxCode($data);

        $this->assertEquals($expectedTaxCode, $taxCode);
    }

    /**
     * @dataProvider testDenormalizeCustomerTaxCodeShouldThrowExceptionDataProvider
     * @param array $data
     * @expectedException \Doctrine\ORM\EntityNotFoundException
     */
    public function testDenormalizeCustomerTaxCodeShouldThrowException(array $data)
    {
        $this->doctrineShouldReturnTagsByCode();
        $this->manager->denormalizeCustomerTaxCode($data);
    }

    /**
     * @dataProvider setTaxCodeWithExistingCustomerDataProvider
     * @param Customer $customer
     * @param CustomerTaxCode $customerTaxCode
     */
    public function testSetTaxCodeWithExistingCustomer(Customer $customer, CustomerTaxCode $customerTaxCode)
    {
        $this->doctrineShouldReturnTaxCodesByCustomer();
        $this->shouldCreateEntityReference($customer->getId());

        $this->manager->setTaxCode($customer, $customerTaxCode);

        $this->assertInstanceOf(Customer::class, $customerTaxCode->getCustomers()->first());
    }

    /**
     * @dataProvider setTaxCodeWithNonExistingCustomerDataProvider
     * @param Customer $customer
     * @param CustomerTaxCode $customerTaxCode
     */
    public function testSetTaxCodeWithNewCustomer(Customer $customer, CustomerTaxCode $customerTaxCode)
    {
        $this->doctrineShouldReturnTaxCodesByCustomer();
        $this->shouldNotCreateEntityReference();

        $this->manager->setTaxCode($customer, $customerTaxCode);

        $this->assertSame($customer, $customerTaxCode->getCustomers()->first());
    }

    /**
     * @dataProvider setTaxCodeChangeTagForCustomerDataProvider
     * @param Customer $customer
     * @param CustomerTaxCode $oldCustomerTaxCode
     * @param CustomerTaxCode $newCustomerTaxCode
     */
    public function testSetTaxCodeChangeTagForCustomer(
        Customer $customer,
        CustomerTaxCode $oldCustomerTaxCode,
        CustomerTaxCode $newCustomerTaxCode
    ) {
        $this->doctrineShouldReturnSingleTaxCodeByCustomer($customer, $oldCustomerTaxCode);
        $this->doctrineShouldFlushOnce();
        $this->shouldCreateEntityReference($customer->getId());

        $this->assertTrue($oldCustomerTaxCode->getCustomers()->contains($customer));
        $this->assertFalse($newCustomerTaxCode->getCustomers()->contains($customer));

        $this->manager->setTaxCode($customer, $newCustomerTaxCode);

        $this->assertFalse($oldCustomerTaxCode->getCustomers()->contains($customer));
        //Since in this scenario we are creating reference, objects won't be the same
        //So we can't use contains()
        $this->assertEquals($customer, $newCustomerTaxCode->getCustomers()->first());
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

    /**
     * @return array
     */
    public function denormalizeCustomerTaxCodeDataProvider()
    {
        $this->createCustomersTaxCodes();

        return [
            [$this->customerTaxCodes[0], ['tax_code' => ['code' => 'TaxCode1']]],
            [$this->customerTaxCodes[1], ['tax_code' => ['code' => 'TaxCode2']]],
        ];
    }

    public function testDenormalizeCustomerTaxCodeShouldThrowExceptionDataProvider()
    {
        return [
            [['tax_code' => ['code' => 'NoneExistingCode']]],
        ];
    }

    /**
     * @return array
     */
    public function setTaxCodeWithExistingCustomerDataProvider()
    {
        $this->createCustomers();
        $this->createCustomersTaxCodes();

        return [
            [$this->customers[1], $this->customerTaxCodes[0]],
            [$this->customers[2], $this->customerTaxCodes[0]],
            [$this->customers[1], $this->customerTaxCodes[1]],
            [$this->customers[2], $this->customerTaxCodes[1]],
        ];
    }

    /**
     * @return array
     */
    public function setTaxCodeWithNonExistingCustomerDataProvider()
    {
        $this->createCustomersTaxCodes();

        return [
            [new Customer(), $this->customerTaxCodes[0]],
            [$this->getEntity(Customer::class, ['name' => 'Customer']), $this->customerTaxCodes[1]],
        ];
    }

    /**
     * @return array
     */
    public function setTaxCodeChangeTagForCustomerDataProvider()
    {
        $this->createCustomers();

        return [
            [
                $this->customers[1],
                $this->getEntity(CustomerTaxCode::class, ['id' => 1])->addCustomer($this->customers[1]),
                $this->getEntity(CustomerTaxCode::class, ['id' => 2]),
            ],
            [
                $this->customers[2],
                $this->getEntity(CustomerTaxCode::class, ['id' => 1])->addCustomer($this->customers[2]),
                $this->getEntity(CustomerTaxCode::class, ['id' => 2]),
            ],
        ];
    }

    /**
     * Prepare map of responses according to $this->customers
     */
    private function doctrineShouldReturnTaxCodesByCustomer()
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

        $this->repository->expects($this->any())
            ->method('findOneByCustomer')
            ->willReturnMap($map);
    }

    /**
     * Prepare single findOneByCustomer response with provided $customer and $customerTaxCode
     * @param Customer $customer
     * @param CustomerTaxCode $customerTaxCode
     */
    private function doctrineShouldReturnSingleTaxCodeByCustomer(Customer $customer, CustomerTaxCode $customerTaxCode)
    {
        $this->repository->expects($this->once())
            ->method('findOneByCustomer')
            ->with($customer)
            ->willReturn($customerTaxCode);
    }

    private function doctrineShouldReturnTagsByCode()
    {
        $map = [];

        foreach ($this->customerTaxCodes as $customerTaxCode) {
            $map[] = [['code' => $customerTaxCode->getCode()], null, $customerTaxCode];
        }

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->willReturnMap($map);
    }

    /**
     * @param int $id
     */
    private function shouldCreateEntityReference($id)
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with(Customer::class, $id)
            ->willReturn($this->getEntity(Customer::class, ['id' => $id]));
    }

    private function shouldNotCreateEntityReference()
    {
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');
    }

    private function doctrineShouldFlushOnce()
    {
        $this->entityManager->expects($this->once())
            ->method('flush');
    }

    private function createCustomers()
    {
        $this->customers = [
            1 => $this->getEntity(Customer::class, ['id' => 1]),
            2 => $this->getEntity(Customer::class, ['id' => 2]),
        ];
    }

    private function createCustomersTaxCodes()
    {
        $this->customerTaxCodes = [
            $this->getEntity(CustomerTaxCode::class, ['id' => 1, 'code' => 'TaxCode1']),
            $this->getEntity(CustomerTaxCode::class, ['id' => 2, 'code' => 'TaxCode2']),
        ];
    }
}

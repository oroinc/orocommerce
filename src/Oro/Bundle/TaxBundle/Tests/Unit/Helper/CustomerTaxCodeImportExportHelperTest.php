<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Helper;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Entity\Repository\CustomerTaxCodeRepository;
use Oro\Bundle\TaxBundle\Helper\CustomerTaxCodeImportExportHelper;
use Oro\Bundle\TaxBundle\Tests\Unit\Entity\CustomerStub;
use Oro\Component\Testing\Unit\EntityTrait;

class CustomerTaxCodeImportExportHelperTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var CustomerTaxCodeImportExportHelper */
    private $manager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var  \PHPUnit\Framework\MockObject\MockObject|CustomerTaxCodeRepository */
    private $repository;

    /** @var  \PHPUnit\Framework\MockObject\MockObject|EntityManager */
    private $entityManager;

    /** @var  Customer[]|\PHPUnit\Framework\MockObject\MockObject[] */
    private $customers;

    /** @var CustomerTaxCode[] */
    private $customerTaxCodes;

    protected function setUp(): void
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
     */
    public function testDenormalizeCustomerTaxCode(CustomerTaxCode $expectedTaxCode = null, array $data)
    {
        $this->doctrineShouldReturnTagsByCode();
        $taxCode = $this->manager->denormalizeCustomerTaxCode($data);

        $this->assertEquals($expectedTaxCode, $taxCode);
    }

    /**
     * @dataProvider testDenormalizeCustomerTaxCodeShouldThrowExceptionDataProvider
     */
    public function testDenormalizeCustomerTaxCodeShouldThrowException(array $data)
    {
        $this->expectException(\Doctrine\ORM\EntityNotFoundException::class);
        $this->doctrineShouldReturnTagsByCode();
        $this->manager->denormalizeCustomerTaxCode($data);
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
     * Prepare map of responses according to $this->customers
     */
    private function doctrineShouldReturnTaxCodesByCustomer()
    {
        foreach ($this->customers as $customer) {
            $customerTaxCode = $this->getEntity(CustomerTaxCode::class, [
                'id' => $customer->getId(),
                'code' => sprintf('code_%s', $customer->getId()),
                'description' => sprintf('description_%s', $customer->getId())
            ]);

            $customer->setTaxCode($customerTaxCode);
        }
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
            1 => $this->getEntity(CustomerStub::class, ['id' => 1]),
            2 => $this->getEntity(CustomerStub::class, ['id' => 2]),
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

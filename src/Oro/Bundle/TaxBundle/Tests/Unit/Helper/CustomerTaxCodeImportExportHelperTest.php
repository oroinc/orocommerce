<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Helper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
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

    /** @var \PHPUnit\Framework\MockObject\MockObject|CustomerTaxCodeRepository */
    private $repository;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityManager */
    private $entityManager;

    /** @var Customer[]|\PHPUnit\Framework\MockObject\MockObject[] */
    private $customers;

    /** @var CustomerTaxCode[] */
    private $customerTaxCodes;

    protected function setUp(): void
    {
        $this->createCustomers();
        $this->createCustomersTaxCodes();

        $this->repository = $this->createMock(CustomerTaxCodeRepository::class);
        $this->entityManager = $this->createMock(EntityManager::class);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($this->repository);
        $doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->entityManager);

        $this->manager = new CustomerTaxCodeImportExportHelper($doctrineHelper);
    }

    public function testLoadNormalizedCustomerTaxCodes(): void
    {
        $this->doctrineShouldReturnTaxCodesByCustomer();
        $customerTaxCodes = $this->manager->loadNormalizedCustomerTaxCodes($this->customers);

        foreach ($this->customers as $customer) {
            $this->assertEquals(
                ['code' => 'code_' . $customer->getId()],
                $customerTaxCodes[$customer->getId()]
            );
        }
    }

    public function testLoadNormalizedCustomerTaxCodesWithEmptyTaxCode(): void
    {
        $customerWithoutTaxCode = $this->getEntity(CustomerStub::class, ['id' => 1]);
        $customerTaxCodes = $this->manager->loadNormalizedCustomerTaxCodes([$customerWithoutTaxCode]);

        $this->assertCount(1, $customerTaxCodes);
        $this->assertEquals(['code' => ''], $customerTaxCodes[$customerWithoutTaxCode->getId()]);
    }

    /**
     * @dataProvider normalizeCustomerTaxCodeDataProvider
     */
    public function testNormalizeCustomerTaxCode(?string $expectedName, CustomerTaxCode $customerTaxCode)
    {
        $normalizedCustomerTaxCode = $this->manager->normalizeCustomerTaxCode($customerTaxCode);

        $this->assertArrayHasKey('code', $normalizedCustomerTaxCode);
        $this->assertEquals($expectedName, $normalizedCustomerTaxCode['code']);
    }

    /**
     * @dataProvider denormalizeCustomerTaxCodeDataProvider
     */
    public function testDenormalizeCustomerTaxCode(?CustomerTaxCode $expectedTaxCode, array $data)
    {
        $this->doctrineShouldReturnTagsByCode();
        $taxCode = $this->manager->denormalizeCustomerTaxCode($data);

        $this->assertEquals($expectedTaxCode, $taxCode);
    }

    /**
     * @dataProvider denormalizeCustomerTaxCodeShouldThrowExceptionDataProvider
     */
    public function testDenormalizeCustomerTaxCodeShouldThrowException(array $data)
    {
        $this->expectException(EntityNotFoundException::class);
        $this->doctrineShouldReturnTagsByCode();
        $this->manager->denormalizeCustomerTaxCode($data);
    }

    public function normalizeCustomerTaxCodeDataProvider(): array
    {
        return [
            ['test', $this->getEntity(CustomerTaxCode::class, ['code' => 'test'])],
            [null, $this->getEntity(CustomerTaxCode::class)],
        ];
    }

    public function denormalizeCustomerTaxCodeDataProvider(): array
    {
        $this->createCustomersTaxCodes();

        return [
            [$this->customerTaxCodes[0], ['tax_code' => ['code' => 'TaxCode1']]],
            [$this->customerTaxCodes[1], ['tax_code' => ['code' => 'TaxCode2']]],
        ];
    }

    public function denormalizeCustomerTaxCodeShouldThrowExceptionDataProvider(): array
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

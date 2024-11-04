<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Helper;

use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Entity\Repository\CustomerTaxCodeRepository;
use Oro\Bundle\TaxBundle\Helper\CustomerTaxCodeImportExportHelper;
use Oro\Bundle\TaxBundle\Tests\Unit\Entity\CustomerStub;
use Oro\Component\Testing\ReflectionUtil;

class CustomerTaxCodeImportExportHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var CustomerTaxCodeRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var CustomerTaxCodeImportExportHelper */
    private $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->repository = $this->createMock(CustomerTaxCodeRepository::class);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects(self::any())
            ->method('getEntityRepository')
            ->willReturn($this->repository);

        $this->manager = new CustomerTaxCodeImportExportHelper($doctrineHelper);
    }

    private function getCustomer(int $id): CustomerStub
    {
        $customer = new CustomerStub();
        ReflectionUtil::setId($customer, $id);

        return $customer;
    }

    public function testLoadNormalizedCustomerTaxCodes(): void
    {
        $customer = $this->getCustomer(123);
        $customerTaxCode = new CustomerTaxCode();
        $customerTaxCode->setCode('test');
        $customer->setTaxCode($customerTaxCode);

        $customerTaxCodes = $this->manager->loadNormalizedCustomerTaxCodes([$customer]);

        self::assertSame([123 => ['code' => 'test']], $customerTaxCodes);
    }

    public function testLoadNormalizedCustomerTaxCodesForCustomerWithoutTaxCode(): void
    {
        $customerWithoutTaxCode = $this->getCustomer(123);

        $customerTaxCodes = $this->manager->loadNormalizedCustomerTaxCodes([$customerWithoutTaxCode]);

        self::assertSame([123 => ['code' => '']], $customerTaxCodes);
    }

    public function testNormalizeCustomerTaxCode(): void
    {
        $customerTaxCode = $this->createMock(CustomerTaxCode::class);
        $customerTaxCode->expects(self::once())
            ->method('getCode')
            ->willReturn('test');

        $normalizedCustomerTaxCode = $this->manager->normalizeCustomerTaxCode($customerTaxCode);
        self::assertSame(['code' => 'test'], $normalizedCustomerTaxCode);
    }

    public function testNormalizeCustomerTaxCodeWhenCustomerTaxCodeIsNull(): void
    {
        $normalizedCustomerTaxCode = $this->manager->normalizeCustomerTaxCode(null);
        self::assertSame(['code' => ''], $normalizedCustomerTaxCode);
    }

    public function testDenormalizeCustomerTaxCode(): void
    {
        $foundCustomerTaxCode = $this->createMock(CustomerTaxCode::class);

        $this->repository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => 'TaxCode1'])
            ->willReturn($foundCustomerTaxCode);

        $customerTaxCode = $this->manager->denormalizeCustomerTaxCode(['tax_code' => ['code' => 'TaxCode1']]);
        self::assertSame($foundCustomerTaxCode, $customerTaxCode);
    }

    public function testDenormalizeCustomerTaxCodeForUnexpectedData(): void
    {
        $this->repository->expects($this->never())
            ->method('findOneBy');

        $customerTaxCode = $this->manager->denormalizeCustomerTaxCode(['tax_code' => ['id' => 1]]);
        self::assertNull($customerTaxCode);
    }

    public function testDenormalizeCustomerTaxCodeWhenCustomerTaxCodeNotFound(): void
    {
        $this->expectException(EntityNotFoundException::class);

        $this->repository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => 'NoneExistingCode'])
            ->willReturn(null);

        $this->manager->denormalizeCustomerTaxCode(['tax_code' => ['code' => 'NoneExistingCode']]);
    }
}

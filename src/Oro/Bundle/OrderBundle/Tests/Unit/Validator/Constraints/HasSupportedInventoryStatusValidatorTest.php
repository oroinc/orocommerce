<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\OrderBundle\Validator\Constraints\HasSupportedInventoryStatus;
use Oro\Bundle\OrderBundle\Validator\Constraints\HasSupportedInventoryStatusValidator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductWithInventoryStatus;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraints\IsNull;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class HasSupportedInventoryStatusValidatorTest extends ConstraintValidatorTestCase
{
    private const CONFIG_PATH = 'oro_order.frontend_product_visibility';

    private ConfigManager|MockObject $configManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): HasSupportedInventoryStatusValidator
    {
        return new HasSupportedInventoryStatusValidator(
            $this->configManager
        );
    }

    public function testValidateWhenNull(): void
    {
        $this->validator->validate(null, new HasSupportedInventoryStatus());

        $this->assertNoViolation();
    }

    public function testValidateUnsupportedConstraint(): void
    {
        $constraint = new IsNull();

        $this->expectExceptionObject(
            new UnexpectedTypeException($constraint, HasSupportedInventoryStatus::class)
        );

        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testValidateUnsupportedClass(): void
    {
        $value = new \stdClass();

        $this->expectExceptionObject(new UnexpectedValueException($value, Product::class));

        $this->validator->validate(new \stdClass(), new HasSupportedInventoryStatus());
    }

    public function testValidateWhenInventoryStatusIsSupported(): void
    {
        $inventoryStatus = $this->createMock(EnumOptionInterface::class);
        $inventoryStatus->expects(self::any())
            ->method('getId')
            ->willReturn('in_stock');

        $product = new ProductWithInventoryStatus();
        $product->setInventoryStatus($inventoryStatus);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(self::CONFIG_PATH)
            ->willReturn(['in_stock', 'out_of_stock']);

        $this->validator->validate($product, new HasSupportedInventoryStatus());

        $this->assertNoViolation();
    }

    /**
     * @dataProvider getValidateWhenInventoryStatusIsNotSupportedDataProvider
     */
    public function testValidateWhenInventoryStatusIsNotSupported(
        ?string $configPath,
        ?array $supportedStatuses
    ): void {
        $inventoryStatus = $this->createMock(EnumOptionInterface::class);
        $inventoryStatus->expects(self::any())
            ->method('getId')
            ->willReturn('in_stock');

        $product = new ProductWithInventoryStatus();
        $product->setInventoryStatus($inventoryStatus);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with($configPath ?? self::CONFIG_PATH)
            ->willReturn($supportedStatuses);

        $constraint = new HasSupportedInventoryStatus();
        if ($configPath) {
            $constraint->configurationPath = $configPath;
        }

        $this->validator->validate($product, $constraint);

        $this
            ->buildViolation($constraint->message)
            ->setCause($product)
            ->assertRaised();
    }

    public function getValidateWhenInventoryStatusIsNotSupportedDataProvider(): array
    {
        return [
            'no such configuration option' => [
                'configPath' => 'unknownPath',
                'supportedStatuses' => null,
            ],
            'empty array' => [
                'configPath' => null, // will be used default value from constraint
                'supportedStatuses' => [],
            ],
            'not supported status' => [
                'configPath' => null, // will be used default value from constraint
                'supportedStatuses' => ['out_of_stock'],
            ],
        ];
    }
}

<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Repository\CouponRepository;
use Oro\Bundle\PromotionBundle\Validator\Constraints\UniqueCaseInsensitiveCouponCode;
use Oro\Bundle\PromotionBundle\Validator\Constraints\UniqueCaseInsensitiveCouponCodeValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UniqueCaseInsensitiveCouponCodeValidatorTest extends ConstraintValidatorTestCase
{
    private ManagerRegistry&MockObject $registry;
    private ConfigManager&MockObject $configManager;

    protected function setUp(): void
    {
        $this->configManager = self::createMock(ConfigManager::class);
        $this->configManager->expects(self::any())
            ->method('get')
            ->willReturn(true);

        $couponRepository = self::createMock(CouponRepository::class);
        $couponRepository->expects(self::any())
            ->method('getCouponByCode')
            ->willReturnCallback(function ($couponCode) {
                return 'valid code' === $couponCode ? [] : [1];
            });

        $this->registry = self::createMock(ManagerRegistry::class);
        $this->registry->expects(self::any())
            ->method('getRepository')
            ->willReturn($couponRepository);

        parent::setUp();
    }


    protected function createValidator(): UniqueCaseInsensitiveCouponCodeValidator
    {
        return new UniqueCaseInsensitiveCouponCodeValidator(
            $this->registry,
            $this->configManager
        );
    }

    /**
     * @dataProvider validateDataProvider
     */
    public function testValidate(Coupon $entity, bool $violationExpected)
    {
        $constraint = new UniqueCaseInsensitiveCouponCode();
        $this->validator->validate($entity, $constraint);

        if ($violationExpected) {
            $this->buildViolation($constraint->message)
                ->setParameters(['{{ code }}' => 'invalid code'])
                ->assertRaised();
        } else {
            $this->assertNoViolation();
        }
    }

    public function validateDataProvider(): array
    {
        return [
            'valid coupon' => [
                'entity' => (new Coupon())
                    ->setCode('valid code')
                    ->setOrganization(new Organization()),
                'violationExpected' => false,
            ],
            'invalid coupon' => [
                'entity' => (new Coupon())
                    ->setCode('invalid code')
                    ->setOrganization(new Organization()),
                'violationExpected' => true,
            ],
        ];
    }

    public function testValidateWrongEntity()
    {
        $this->expectException(UnexpectedTypeException::class);

        $constraint = new UniqueCaseInsensitiveCouponCode();
        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testValidateWrongConstraint()
    {
        $this->expectException(UnexpectedTypeException::class);

        $constraint = self::createMock(Constraint::class);
        $this->validator->validate(new Coupon(), $constraint);
    }
}

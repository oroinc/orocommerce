<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Repository\CouponRepository;
use Oro\Bundle\PromotionBundle\Validator\Constraints\ConfigCouponCaseInsensitiveOption;
use Oro\Bundle\PromotionBundle\Validator\Constraints\ConfigCouponCaseInsensitiveOptionValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ConfigCouponCaseInsensitiveOptionValidatorValidator extends ConstraintValidatorTestCase
{
    private ManagerRegistry&MockObject $registry;

    protected function setUp(): void
    {
        $this->registry = self::createMock(ManagerRegistry::class);
        parent::setUp();
    }

    protected function createValidator(): ConfigCouponCaseInsensitiveOptionValidator
    {
        return new ConfigCouponCaseInsensitiveOptionValidator($this->registry);
    }

    /**
     * @dataProvider validateDataProvider
     */
    public function testValidate(bool $entity, OrganizationInterface $organization, bool $violationExpected)
    {
        $couponRepository = self::createMock(CouponRepository::class);
        $couponRepository->expects(self::any())
            ->method('hasDuplicatesInCaseInsensitiveMode')
            ->willReturn($violationExpected);

        $this->registry->expects(self::any())
            ->method('getRepository')
            ->willReturn($couponRepository);

        $constraint = new ConfigCouponCaseInsensitiveOption();
        $this->validator->validate($entity, $constraint);

        if ($violationExpected) {
            $this->buildViolation($constraint->message)->assertRaised();
        } else {
            $this->assertNoViolation();
        }
    }

    public function validateDataProvider(): array
    {
        return [
            'Enabled case insensitive option' => [
                'config' => true,
                'organization' => (new Organization())->setName('ORO'),
                'violationExpected' => false,
            ],
            'Disabled case insensitive option' => [
                'config' => true,
                'organization' => (new Organization())->setName('ACME'),
                'violationExpected' => true,
            ],
        ];
    }

    public function testValidateWrongConstraint()
    {
        $this->expectException(UnexpectedTypeException::class);

        $constraint = self::createMock(Constraint::class);
        $this->validator->validate(new Coupon(), $constraint);
    }
}

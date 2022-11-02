<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentRepository;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedConsents;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedConsentsValidator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class RemovedConsentsValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ConsentRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $consentRepository;

    protected function setUp(): void
    {
        $this->consentRepository = $this->createMock(ConsentRepository::class);
        parent::setUp();
    }

    protected function createValidator()
    {
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(Consent::class)
            ->willReturn($this->consentRepository);

        return new RemovedConsentsValidator($doctrineHelper);
    }

    public function testValidateWithIncorrectType()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Incorrect type of the value!');

        $constraint = new RemovedConsents();
        $this->validator->validate('not array', $constraint);
    }

    /**
     * @dataProvider validateProvider
     */
    public function testValidate(array $value, array $checkedConsentIds, array $nonExistentConsentIds)
    {
        $value = new ArrayCollection($value);

        if (empty($checkedConsentIds)) {
            $this->consentRepository->expects($this->never())
                ->method('getNonExistentConsentIds');
        } else {
            $this->consentRepository->expects($this->once())
                ->method('getNonExistentConsentIds')
                ->with($checkedConsentIds)
                ->willReturn($nonExistentConsentIds);
        }

        $constraint = new RemovedConsents();
        $this->validator->validate($value, $constraint);

        if (empty($nonExistentConsentIds)) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($constraint->message)
                ->assertRaised();
        }
    }

    public function validateProvider(): array
    {
        $consent1 = new Consent();
        ReflectionUtil::setId($consent1, 1);
        $consentAcceptanceWithExistedConsent = new ConsentAcceptance();
        ReflectionUtil::setId($consentAcceptanceWithExistedConsent, 7);
        $consentAcceptanceWithExistedConsent->setConsent($consent1);

        $consent2 = new Consent();
        ReflectionUtil::setId($consent2, 2);
        $consentAcceptanceWithNonExistentConsent = new ConsentAcceptance();
        ReflectionUtil::setId($consentAcceptanceWithNonExistentConsent, 8);
        $consentAcceptanceWithNonExistentConsent->setConsent($consent2);

        return [
            'Empty value' => [
                'value' => [],
                'checkedConsentIds' => [],
                'nonExistentConsentIds' => []
            ],
            'Only existed consent in value' => [
                'value' => [$consentAcceptanceWithExistedConsent],
                'checkedConsentIds' => [1],
                'nonExistentConsentIds' => []
            ],
            'Only not existed consent in value' => [
                'value' => [$consentAcceptanceWithNonExistentConsent],
                'checkedConsentIds' => [2],
                'nonExistentConsentIds' => [2]
            ]
        ];
    }
}

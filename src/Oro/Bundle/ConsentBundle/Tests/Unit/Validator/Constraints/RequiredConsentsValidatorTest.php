<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Provider\EnabledConsentProvider;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RequiredConsents;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RequiredConsentsValidator;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class RequiredConsentsValidatorTest extends ConstraintValidatorTestCase
{
    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizedHelper;

    /** @var EnabledConsentProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $enabledConsentProvider;

    protected function setUp(): void
    {
        $this->localizedHelper = $this->createMock(LocalizationHelper::class);
        $this->localizedHelper->expects($this->any())
            ->method('getLocalizedValue')
            ->willReturnCallback(function (ArrayCollection $value) {
                /** @var LocalizedFallbackValue $fallbackValue */
                $fallbackValue = $value->first();

                return $fallbackValue->getString();
            });
        $this->enabledConsentProvider = $this->createMock(EnabledConsentProvider::class);
        parent::setUp();
    }

    protected function createValidator()
    {
        return new RequiredConsentsValidator($this->enabledConsentProvider, $this->localizedHelper);
    }

    public function testIncorrectTypeValidateValue()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Incorrect type of the value!');

        $constraint = new RequiredConsents();
        $this->validator->validate('', $constraint);
    }

    /**
     * @dataProvider validateProvider
     *
     * @param ConsentAcceptance[] $validatedValue
     * @param Consent[]           $requiredConsents
     * @param bool                $isValid
     * @param array               $violationParameters
     */
    public function testValidate(
        array $validatedValue,
        array $requiredConsents,
        bool $isValid,
        array $violationParameters
    ) {
        $validatedValue = new ArrayCollection($validatedValue);
        $this->enabledConsentProvider->expects($this->once())
            ->method('getUnacceptedRequiredConsents')
            ->willReturn($requiredConsents);

        $constraint = new RequiredConsents();
        $this->validator->validate($validatedValue, $constraint);

        if ($isValid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($constraint->message)
                ->setParameters($violationParameters)
                ->assertRaised();
        }
    }

    public function validateProvider(): array
    {
        return [
            'All required consents checked' => [
                'validatedValue' => [
                    $this->createConsentAcceptanceWithConsent(1),
                    $this->createConsentAcceptanceWithConsent(2),
                ],
                'requiredConsents' => [],
                'isValid' => true,
                'violationParameters' => []
            ],
            'Unchecked required consents exist' => [
                'validatedValue' => [
                    $this->createConsentAcceptanceWithConsent(1),
                ],
                'requiredConsents' => [
                    $this->createConsent(1),
                    $this->createConsent(2),
                    $this->createConsent(3),
                ],
                'isValid' => false,
                'violationParameters' => [
                    '{{ consent_names }}' => '"consent_1", "consent_2", "consent_3"'
                ]
            ]
        ];
    }

    private function createConsentAcceptanceWithConsent(int $consentId): ConsentAcceptance
    {
        $fallbackValue = new LocalizedFallbackValue();
        $fallbackValue->setString('consent_' . $consentId);

        $consent = new Consent();
        ReflectionUtil::setId($consent, $consentId);
        $consent->addName($fallbackValue);

        $consentAcceptance = new ConsentAcceptance();
        $consentAcceptance->setConsent($consent);

        return $consentAcceptance;
    }

    private function createConsent(int $consentId): Consent
    {
        $fallbackValue = new LocalizedFallbackValue();
        $fallbackValue->setString('consent_' . $consentId);

        $consent = new Consent();
        ReflectionUtil::setId($consent, $consentId);
        $consent->addName($fallbackValue);

        return $consent;
    }
}

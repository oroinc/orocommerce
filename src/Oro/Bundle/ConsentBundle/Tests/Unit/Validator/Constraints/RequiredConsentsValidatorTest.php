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
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class RequiredConsentsValidatorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizedHelper;

    /** @var EnabledConsentProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $enabledConsentProvider;

    /** @var RequiredConsentsValidator */
    private $validator;

    /** @var RequiredConsents */
    private $constraint;

    /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $context;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
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
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->validator = new RequiredConsentsValidator($this->enabledConsentProvider, $this->localizedHelper);
        $this->validator->initialize($this->context);
        $this->constraint = new RequiredConsents();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->localizedHelper,
            $this->enabledConsentProvider,
            $this->validator,
            $this->constraint
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Incorrect type of the value!
     */
    public function testIncorrectTypeValidateValue()
    {
        $this->validator->validate('', $this->constraint);
    }

    /**
     * @dataProvider validateProvider
     *
     * @param ConsentAcceptance[] $validatedValue
     * @param Consent[] $requiredConsents
     * @param bool $isValid
     * @param array $violationParameters
     */
    public function testValidate(
        array $validatedValue,
        array $requiredConsents,
        $isValid,
        array $violationParameters
    ) {
        $validatedValue = new ArrayCollection($validatedValue);
        $this->enabledConsentProvider
            ->expects($this->once())
            ->method('getUnacceptedRequiredConsents')
            ->willReturn($requiredConsents);
        
        if (!$isValid) {
            $this->context
                ->expects($this->once())
                ->method('buildViolation')
                ->with($this->constraint->message, $violationParameters)
                ->willReturn(
                    $this->createMock(ConstraintViolationBuilderInterface::class)
                );
        }

        $this->validator->validate($validatedValue, $this->constraint);
    }

    /**
     * @return array
     */
    public function validateProvider()
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

    /**
     * @param int $consentId
     *
     * @return ConsentAcceptance
     */
    private function createConsentAcceptanceWithConsent(int $consentId)
    {
        $fallbackValue = new LocalizedFallbackValue();
        $fallbackValue->setString('consent_' . $consentId);

        return $this->getEntity(
            ConsentAcceptance::class,
            [
                'consent' => $this->getEntity(Consent::class, [
                    'id' => $consentId,
                    'names' => new ArrayCollection([$fallbackValue])
                ])
            ]
        );
    }

    /**
     * @param int $consentId
     *
     * @return Consent
     */
    private function createConsent(int $consentId)
    {
        $fallbackValue = new LocalizedFallbackValue();
        $fallbackValue->setString('consent_' . $consentId);

        return $this->getEntity(Consent::class, [
            'id' => $consentId,
            'names' => new ArrayCollection([$fallbackValue])
        ]);
    }
}

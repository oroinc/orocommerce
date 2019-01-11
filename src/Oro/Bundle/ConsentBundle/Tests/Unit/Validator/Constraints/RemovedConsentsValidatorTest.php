<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentRepository;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedConsents;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedConsentsValidator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class RemovedConsentsValidatorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var RemovedConsentsValidator */
    private $validator;

    /** @var ConsentRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $consentRepository;

    /** @var RemovedConsents */
    private $constraint;

    /** @var ExecutionContext|\PHPUnit\Framework\MockObject\MockObject $context */
    private $context;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->consentRepository = $this->createMock(ConsentRepository::class);
        /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper */
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper
            ->expects($this->any())
            ->method('getEntityRepository')
            ->with(Consent::class)
            ->willReturn($this->consentRepository);

        $this->validator = new RemovedConsentsValidator($doctrineHelper);
        $this->context = $this->createMock(ExecutionContext::class);
        $this->validator->initialize($this->context);

        $this->constraint = new RemovedConsents();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->consentRepository);
        unset($this->validator);
        unset($this->context);
        unset($this->constraint);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Incorrect type of the value!
     */
    public function testValidateWithIncorrectType()
    {
        $this->validator->validate('not array', $this->constraint);
    }

    /**
     * @dataProvider validateProvider
     *
     * @param array $value
     * @param array $checkedConsentIds
     * @param array $nonExistentConsentIds
     */
    public function testValidate(array $value, array $checkedConsentIds, array $nonExistentConsentIds)
    {
        $value = new ArrayCollection($value);

        if (empty($checkedConsentIds)) {
            $this->consentRepository
                ->expects($this->never())
                ->method('getNonExistentConsentIds');
        } else {
            $this->consentRepository
                ->expects($this->once())
                ->method('getNonExistentConsentIds')
                ->with($checkedConsentIds)
                ->willReturn($nonExistentConsentIds);
        }

        if (empty($nonExistentConsentIds)) {
            $this->context
                ->expects($this->never())
                ->method('buildViolation');
        } else {
            $this->context
                ->expects($this->once())
                ->method('buildViolation')
                ->with($this->constraint->message)
                ->willReturn(
                    $this->createMock(ConstraintViolationBuilderInterface::class)
                );
        }

        $this->validator->validate($value, $this->constraint);
    }

    /**
     * @return array
     */
    public function validateProvider()
    {
        $consentAcceptanceWithExistedConsent = $this->getEntity(
            ConsentAcceptance::class,
            [
                'id' => 7,
                'consent' => $this->getEntity(Consent::class, ['id' => 1])
            ]
        );

        $consentAcceptanceWithNonExistentConsent = $this->getEntity(
            ConsentAcceptance::class,
            [
                'id' => 8,
                'consent' => $this->getEntity(Consent::class, ['id' => 2])
            ]
        );

        return [
            "Empty value" => [
                'value' => [],
                'checkedConsentIds' => [],
                'nonExistentConsentIds' => []
            ],
            "Only existed consent in value" => [
                'value' => [$consentAcceptanceWithExistedConsent],
                'checkedConsentIds' => [1],
                'nonExistentConsentIds' => []
            ],
            "Only not existed consent in value" => [
                'value' => [$consentAcceptanceWithNonExistentConsent],
                'checkedConsentIds' => [2],
                'nonExistentConsentIds' => [2]
            ]
        ];
    }
}

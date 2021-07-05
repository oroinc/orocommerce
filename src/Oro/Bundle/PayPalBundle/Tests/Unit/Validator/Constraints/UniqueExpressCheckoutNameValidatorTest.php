<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Entity\Repository\PayPalSettingsRepository;
use Oro\Bundle\PayPalBundle\Validator\Constraints\UniqueExpressCheckoutName;
use Oro\Bundle\PayPalBundle\Validator\Constraints\UniqueExpressCheckoutNameValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UniqueExpressCheckoutNameValidatorTest extends TestCase
{
    /**
     * @var DoctrineHelper|MockObject
     */
    private $doctrineHelper;

    /**
     * @var ExecutionContextInterface|MockObject
     */
    private $context;

    /**
     * @var UniqueExpressCheckoutNameValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);

        /** @var TranslatorInterface $translator */
        $translator = $this->createMock(TranslatorInterface::class);

        $this->validator = new UniqueExpressCheckoutNameValidator(
            $this->doctrineHelper,
            $translator
        );

        $this->validator->initialize($this->context);
    }

    public function testValidateInvalidConstraint()
    {
        $this->expectException(UnexpectedTypeException::class);

        /** @var Constraint $constraint */
        $constraint = $this->getMockForAbstractClass(Constraint::class);

        $this->validator->validate(new PayPalSettings(), $constraint);
    }

    public function testValidateEmptyValues()
    {
        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate(new \stdClass(), new UniqueExpressCheckoutName());
    }

    public function testValidateExpressCheckoutNamePassed()
    {
        $constraint = new UniqueExpressCheckoutName();

        $payPalSettings = new PayPalSettings();
        $payPalSettings
            ->setExpressCheckoutName('expressCheckoutName');

        $integration = new Channel();
        $integration->setName('integrationName');
        $integration->setTransport($payPalSettings);

        $channelRepository = $this->createMock(ChannelRepository::class);
        $channelRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => 'expressCheckoutName'])
            ->willReturn(null);

        $payPalSettingsRepository = $this->createMock(PayPalSettingsRepository::class);
        $payPalSettingsRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['expressCheckoutName' => 'integrationName'])
            ->willReturn(null);

        $this->doctrineHelper
            ->expects($this->exactly(2))
            ->method('getEntityRepository')
            ->withConsecutive([Channel::class], [PayPalSettings::class])
            ->willReturnOnConsecutiveCalls($channelRepository, $payPalSettingsRepository);

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($integration, $constraint);
    }

    public function testValidateExpressCheckoutSameName()
    {
        $constraint = new UniqueExpressCheckoutName();

        $payPalSettings = new PayPalSettings();
        $payPalSettings->setExpressCheckoutName('expressCheckoutName');

        $integration = new Channel();
        $integration
            ->setName('expressCheckoutName')
            ->setTransport($payPalSettings);

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->expressCheckoutNameMessage)
            ->willReturn($this->createMock(ConstraintViolationBuilderInterface::class));

        $this->validator->validate($integration, $constraint);
    }

    public function testValidateExpressCheckoutAlreadyUsed()
    {
        $constraint = new UniqueExpressCheckoutName();

        $payPalSettings = new PayPalSettings();
        $payPalSettings->setExpressCheckoutName('expressCheckoutName');

        $integration = new Channel();
        $integration
            ->setName('integrationName')
            ->setTransport($payPalSettings);

        $channelRepository = $this->createMock(ChannelRepository::class);
        $channelRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => 'expressCheckoutName'])
            ->willReturn(new \stdClass());

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with(Channel::class)
            ->willReturn($channelRepository);

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->expressCheckoutNameMessage)
            ->willReturn($this->createMock(ConstraintViolationBuilderInterface::class));

        $this->validator->validate($integration, $constraint);
    }

    public function testValidateIntegrationNamaUniqueness()
    {
        $constraint = new UniqueExpressCheckoutName();

        /** @var Transport|MockObject $transport */
        $transport = $this->getMockForAbstractClass(Transport::class);

        $integration = new Channel();
        $integration
            ->setName('integrationName')
            ->setTransport($transport);

        $payPalSettingsRepository = $this->createMock(PayPalSettingsRepository::class);
        $payPalSettingsRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['expressCheckoutName' => 'integrationName'])
            ->willReturn(new \stdClass());

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with(PayPalSettings::class)
            ->willReturn($payPalSettingsRepository);

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->willReturn($this->createMock(ConstraintViolationBuilderInterface::class));

        $this->validator->validate($integration, $constraint);
    }
}

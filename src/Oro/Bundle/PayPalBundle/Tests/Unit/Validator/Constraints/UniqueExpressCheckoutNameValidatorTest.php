<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Entity\Repository\PayPalSettingsRepository;
use Oro\Bundle\PayPalBundle\Validator\Constraints\UniqueExpressCheckoutName;
use Oro\Bundle\PayPalBundle\Validator\Constraints\UniqueExpressCheckoutNameValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UniqueExpressCheckoutNameValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        parent::setUp();
    }

    protected function createValidator()
    {
        return new UniqueExpressCheckoutNameValidator($this->doctrine);
    }

    public function testValidateInvalidConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new PayPalSettings(), $this->createMock(Constraint::class));
    }

    public function testValidateEmptyValues(): void
    {
        $this->validator->validate(new \stdClass(), new UniqueExpressCheckoutName());

        $this->assertNoViolation();
    }

    public function testValidateExpressCheckoutNamePassed(): void
    {
        $constraint = new UniqueExpressCheckoutName();

        $payPalSettings = new PayPalSettings();
        $payPalSettings
            ->setExpressCheckoutName('expressCheckoutName');

        $integration = new Channel();
        $integration->setName('integrationName');
        $integration->setTransport($payPalSettings);

        $channelRepository = $this->createMock(ChannelRepository::class);
        $channelRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['name' => 'expressCheckoutName'])
            ->willReturn(null);

        $payPalSettingsRepository = $this->createMock(PayPalSettingsRepository::class);
        $payPalSettingsRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['expressCheckoutName' => 'integrationName'])
            ->willReturn(null);

        $this->doctrine->expects(self::exactly(2))
            ->method('getRepository')
            ->withConsecutive([Channel::class], [PayPalSettings::class])
            ->willReturnOnConsecutiveCalls($channelRepository, $payPalSettingsRepository);

        $this->validator->validate($integration, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateExpressCheckoutSameName(): void
    {
        $constraint = new UniqueExpressCheckoutName();

        $payPalSettings = new PayPalSettings();
        $payPalSettings->setExpressCheckoutName('expressCheckoutName');

        $integration = new Channel();
        $integration
            ->setName('expressCheckoutName')
            ->setTransport($payPalSettings);

        $this->validator->validate($integration, $constraint);

        $this->buildViolation($constraint->expressCheckoutNameMessage)->assertRaised();
    }

    public function testValidateExpressCheckoutAlreadyUsed(): void
    {
        $constraint = new UniqueExpressCheckoutName();

        $payPalSettings = new PayPalSettings();
        $payPalSettings->setExpressCheckoutName('expressCheckoutName');

        $integration = new Channel();
        $integration
            ->setName('integrationName')
            ->setTransport($payPalSettings);

        $channelRepository = $this->createMock(ChannelRepository::class);
        $channelRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['name' => 'expressCheckoutName'])
            ->willReturn(new \stdClass());

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(Channel::class)
            ->willReturn($channelRepository);

        $this->validator->validate($integration, $constraint);

        $this->buildViolation($constraint->expressCheckoutNameMessage)->assertRaised();
    }

    public function testValidateIntegrationNameUniqueness(): void
    {
        $constraint = new UniqueExpressCheckoutName();

        $transport = $this->createMock(Transport::class);

        $integration = new Channel();
        $integration
            ->setName('integrationName')
            ->setTransport($transport);

        $payPalSettingsRepository = $this->createMock(PayPalSettingsRepository::class);
        $payPalSettingsRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['expressCheckoutName' => 'integrationName'])
            ->willReturn(new \stdClass());

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(PayPalSettings::class)
            ->willReturn($payPalSettingsRepository);

        $this->validator->validate($integration, $constraint);

        $this
            ->buildViolation($constraint->integrationNameUniquenessMessage)
            ->setParameters(['%name%' => 'integrationName'])
            ->assertRaised();
    }
}

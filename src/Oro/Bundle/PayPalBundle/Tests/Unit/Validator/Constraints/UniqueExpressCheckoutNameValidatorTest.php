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
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class UniqueExpressCheckoutNameValidatorTest extends ConstraintValidatorTestCase
{
    private DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        parent::setUp();
    }

    protected function createValidator()
    {
        /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->withAnyParameters()
            ->willReturnCallback(static fn ($id) => $id . ' (translated)');

        return new UniqueExpressCheckoutNameValidator(
            $this->doctrineHelper,
            $translator
        );
    }

    public function testValidateInvalidConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        /** @var Constraint $constraint */
        $constraint = $this->getMockForAbstractClass(Constraint::class);

        $this->validator->validate(new PayPalSettings(), $constraint);
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
        $channelRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['name' => 'expressCheckoutName'])
            ->willReturn(null);

        $payPalSettingsRepository = $this->createMock(PayPalSettingsRepository::class);
        $payPalSettingsRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['expressCheckoutName' => 'integrationName'])
            ->willReturn(null);

        $this->doctrineHelper
            ->expects(self::exactly(2))
            ->method('getEntityRepository')
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
        $channelRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['name' => 'expressCheckoutName'])
            ->willReturn(new \stdClass());

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityRepository')
            ->with(Channel::class)
            ->willReturn($channelRepository);

        $this->validator->validate($integration, $constraint);

        $this->buildViolation($constraint->expressCheckoutNameMessage)->assertRaised();
    }

    public function testValidateIntegrationNameUniqueness(): void
    {
        $constraint = new UniqueExpressCheckoutName();

        /** @var Transport|\PHPUnit\Framework\MockObject\MockObject $transport */
        $transport = $this->getMockForAbstractClass(Transport::class);

        $integration = new Channel();
        $integration
            ->setName('integrationName')
            ->setTransport($transport);

        $payPalSettingsRepository = $this->createMock(PayPalSettingsRepository::class);
        $payPalSettingsRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['expressCheckoutName' => 'integrationName'])
            ->willReturn(new \stdClass());

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityRepository')
            ->with(PayPalSettings::class)
            ->willReturn($payPalSettingsRepository);

        $this->validator->validate($integration, $constraint);

        $this->buildViolation($constraint->integrationNameUniquenessMessage . ' (translated)')->assertRaised();
    }
}

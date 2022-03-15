<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Notification;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EmailBundle\Builder\EmailModelBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Sender\EmailModelSender;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Notification\NotificationHelper;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\Request;

class NotificationHelperTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;
    use LoggerAwareTraitTestTrait;

    private EmailModelBuilder|\PHPUnit\Framework\MockObject\MockObject $emailModelBuilder;

    private EmailModelSender|\PHPUnit\Framework\MockObject\MockObject $emailModelSender;

    private FeatureChecker|\PHPUnit\Framework\MockObject\MockObject $featureChecker;

    private NotificationHelper $helper;

    private EntityManager|\PHPUnit\Framework\MockObject\MockObject $entityManager;

    protected function setUp(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManager::class);

        $registry->expects(self::any())
            ->method('getManagerForClass')
            ->with(EmailTemplate::class)
            ->willReturn($this->entityManager);

        $this->emailModelBuilder = $this->createMock(EmailModelBuilder::class);
        $this->emailModelSender = $this->createMock(EmailModelSender::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->helper = new NotificationHelper(
            $registry,
            $this->emailModelBuilder,
            $this->emailModelSender,
            $this->featureChecker
        );
        $this->helper->setQuoteClassName(Quote::class);
        $this->helper->setEmailTemplateClassName(EmailTemplate::class);
        $this->setUpLoggerMock($this->helper);
    }

    public function testGetEmailModel(): void
    {
        $request = new Request(['entityClass' => Quote::class, 'entityId' => 42]);
        $request->setMethod('GET');

        $this->emailModelBuilder->expects(self::once())
            ->method('createEmailModel')
            ->willReturn(new Email());

        $this->emailModelBuilder->expects(self::once())->method('setRequest')->with($request);

        $customerUser = new CustomerUser();
        $customerUser->setEmail('test@example.com');

        /** @var Quote $quote */
        $quote = $this->getEntity(
            Quote::class,
            ['id' => 42, 'customerUser' => $customerUser, 'organization' => new Organization()]
        );

        $this->assertRepositoryCalled(EmailTemplate::class);
        self::assertEquals(
            $this->createEmailModel($quote, 'test@example.com', Quote::class, 42, 'quote_email_link'),
            $this->helper->getEmailModel($quote)
        );
    }

    /**
     * @dataProvider guestAccessProvider
     */
    public function testGetEmailModelGuestAccess(
        bool $isGuestQuoteEnabled,
        ?CustomerUser $customerUser,
        string $expectedTemplate
    ): void {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('guest_quote')
            ->willReturn($isGuestQuoteEnabled);

        $request = new Request(['entityClass' => Quote::class, 'entityId' => 42]);
        $request->setMethod('GET');

        $this->emailModelBuilder->expects(self::once())
            ->method('createEmailModel')
            ->willReturn(new Email());

        $this->emailModelBuilder->expects(self::once())->method('setRequest')->with($request);

        /** @var Quote $quote */
        $quote = $this->getEntity(Quote::class, ['id' => 42, 'customerUser' => $customerUser]);

        $this->assertRepositoryCalled(EmailTemplate::class);
        self::assertEquals(
            $this->createEmailModel(
                $quote,
                $customerUser?->getEmail(),
                Quote::class,
                42,
                $expectedTemplate
            ),
            $this->helper->getEmailModel($quote)
        );
    }

    public function guestAccessProvider(): array
    {
        $common = new CustomerUser();
        $common->setEmail('test@example.com');

        $guest = clone $common;
        $guest->setIsGuest(true);

        return [
            'feature disabled' => [
                'isGuestQuoteEnabled' => false,
                'customerUser' => $guest,
                'expectedTemplate' => 'quote_email_link',
            ],
            'feature enabled with common user' => [
                'isGuestQuoteEnabled' => true,
                'customerUser' => $common,
                'expectedTemplate' => 'quote_email_link',
            ],
            'feature enabled without user' => [
                'isGuestQuoteEnabled' => true,
                'customerUser' => null,
                'expectedTemplate' => 'quote_email_link_guest',
            ],
            'feature enabled with guest user' => [
                'isGuestQuoteEnabled' => true,
                'customerUser' => $guest,
                'expectedTemplate' => 'quote_email_link_guest',
            ],
        ];
    }

    public function testSend(): void
    {
        /** @var Quote $quote */
        $quote = $this->getEntity(Quote::class, ['id' => 42]);
        $emailModel = $this->createEmailModel($quote, 'test@example.com', 'stdClass', 42, 'quote_email_link');

        $this->emailModelSender->expects(self::once())->method('send')->with($emailModel);

        $this->helper->send($emailModel);
    }

    public function testSendLogsErrorWhenException(): void
    {
        /** @var Quote $quote */
        $quote = $this->getEntity(Quote::class, ['id' => 42]);
        $emailModel = $this->createEmailModel($quote, 'test@example.com', 'stdClass', 42, 'quote_email_link');

        $exception = new \RuntimeException('Sample exception');
        $this->emailModelSender
            ->expects(self::once())
            ->method('send')
            ->with($emailModel)
            ->willThrowException($exception);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->willReturnCallback(static function (string $message, array $context) use ($exception, $emailModel) {
                self::assertEquals('Failed to send email model to test@example.com: Sample exception', $message);
                self::assertSame($exception, $context['exception']);
                self::assertSame($emailModel, $context['emailModel']);
            });

        $this->helper->send($emailModel);
    }

    public function testSendDisabled(): void
    {
        /** @var Quote $quote */
        $quote = $this->getEntity(Quote::class, ['id' => 42]);
        $emailModel = $this->createEmailModel($quote, 'test@example.com', 'stdClass', 42, 'quote_email_link');

        $this->emailModelSender->expects(self::never())->method(self::anything());

        $this->helper->setEnabled(false);
        $this->helper->send($emailModel);
    }

    private function assertRepositoryCalled(string $className): void
    {
        $commonTemplate = new EmailTemplate();
        $commonTemplate->setName('quote_email_link');

        $guestTemplate = new EmailTemplate();
        $guestTemplate->setName('quote_email_link_guest');

        /** @var \PHPUnit\Framework\MockObject\MockObject|ObjectRepository $repository */
        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(self::any())
            ->method('findOneBy')
            ->willReturnMap(
                [
                    [['name' => $commonTemplate->getName()], $commonTemplate],
                    [['name' => $guestTemplate->getName()], $guestTemplate],
                ]
            );

        $this->entityManager->expects(self::once())
            ->method('getRepository')
            ->with($className)
            ->willReturn($repository);
    }

    private function createEmailModel(
        Quote $quote,
        ?string $email,
        string $entityClass,
        int $entityId,
        string $template
    ): Email {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName($template);

        $emailModel = new Email();
        $emailModel
            ->setTo([$email])
            ->setEntityClass($entityClass)
            ->setEntityId($entityId)
            ->setContexts([$quote])
            ->setTemplate($emailTemplate);

        if ($quote->getOrganization()) {
            $emailModel->setOrganization($quote->getOrganization());
        }

        return $emailModel;
    }
}

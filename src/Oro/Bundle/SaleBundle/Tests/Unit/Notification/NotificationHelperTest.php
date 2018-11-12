<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Notification;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EmailBundle\Builder\EmailModelBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Component\Testing\Unit\EntityTrait;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Notification\NotificationHelper;

class NotificationHelperTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EmailModelBuilder */
    protected $emailModelBuilder;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Processor */
    protected $emailProcessor;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FeatureChecker */
    protected $featureChecker;

    /** @var NotificationHelper */
    protected $helper;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->emailModelBuilder = $this->createMock(EmailModelBuilder::class);
        $this->emailProcessor = $this->createMock(Processor::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->helper = new NotificationHelper(
            $this->registry,
            $this->emailModelBuilder,
            $this->emailProcessor
        );
        $this->helper->setQuoteClassName(Quote::class);
        $this->helper->setEmailTemplateClassName(EmailTemplate::class);
    }

    protected function tearDown()
    {
        unset($this->helper, $this->registry, $this->emailModelBuilder, $this->emailProcessor);
    }

    public function testGetEmailModel()
    {
        $request = new Request(['entityClass' => Quote::class, 'entityId' => 42]);
        $request->setMethod('GET');

        $this->emailModelBuilder->expects($this->once())
            ->method('createEmailModel')
            ->willReturn(new Email());

        $this->emailModelBuilder->expects($this->once())->method('setRequest')->with($request);

        $customerUser = new CustomerUser();
        $customerUser->setEmail('test@example.com');

        /** @var Quote $quote */
        $quote = $this->getEntity(Quote::class, ['id' => 42, 'customerUser' => $customerUser]);

        $this->assertRepositoryCalled(EmailTemplate::class);
        $this->assertEquals(
            $this->createEmailModel($quote, 'test@example.com', Quote::class, 42, 'quote_email_link'),
            $this->helper->getEmailModel($quote)
        );
    }

    /**
     * @dataProvider guestAccessProvider
     *
     * @param bool $isGuestQuoteEnabled
     * @param CustomerUser|null $customerUser
     * @param string $expectedTemplate
     */
    public function testGetEmailModelGuestAccess(
        bool $isGuestQuoteEnabled,
        ?CustomerUser $customerUser,
        string $expectedTemplate
    ) {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_quote')
            ->willReturn($isGuestQuoteEnabled);

        $this->helper->setFeatureChecker($this->featureChecker);

        $request = new Request(['entityClass' => Quote::class, 'entityId' => 42]);
        $request->setMethod('GET');

        $this->emailModelBuilder->expects($this->once())
            ->method('createEmailModel')
            ->willReturn(new Email());

        $this->emailModelBuilder->expects($this->once())->method('setRequest')->with($request);

        /** @var Quote $quote */
        $quote = $this->getEntity(Quote::class, ['id' => 42, 'customerUser' => $customerUser]);

        $this->assertRepositoryCalled(EmailTemplate::class);
        $this->assertEquals(
            $this->createEmailModel(
                $quote,
                $customerUser ? $customerUser->getEmail() : null,
                Quote::class,
                42,
                $expectedTemplate
            ),
            $this->helper->getEmailModel($quote)
        );
    }

    /**
     * @return array
     */
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
                'expectedTemplate' => 'quote_email_link'
            ],
            'feature enabled with common user' => [
                'isGuestQuoteEnabled' => true,
                'customerUser' => $common,
                'expectedTemplate' => 'quote_email_link'
            ],
            'feature enabled without user' => [
                'isGuestQuoteEnabled' => true,
                'customerUser' => null,
                'expectedTemplate' => 'quote_email_link_guest'
            ],
            'feature enabled with guest user' => [
                'isGuestQuoteEnabled' => true,
                'customerUser' => $guest,
                'expectedTemplate' => 'quote_email_link_guest'
            ]
        ];
    }

    public function testSend()
    {
        /** @var Quote $quote */
        $quote = $this->getEntity(Quote::class, ['id' => 42]);
        $emailModel = $this->createEmailModel($quote, 'test@example.com', 'stdClass', 42, 'quote_email_link');

        $this->emailProcessor->expects($this->once())->method('process')->with($emailModel);
        $this->registry->expects($this->never())->method($this->anything());

        $this->helper->send($emailModel);
    }

    /**
     * @param string $className
     * @return \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected function assertManagerCalled($className)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager $manager */
        $manager = $this->createMock('Doctrine\Common\Persistence\ObjectManager');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($className)
            ->willReturn($manager);

        return $manager;
    }

    /**
     * @param string $className
     */
    protected function assertRepositoryCalled($className)
    {
        $commonTemplate = new EmailTemplate();
        $commonTemplate->setName('quote_email_link');

        $guestTemplate = new EmailTemplate();
        $guestTemplate->setName('quote_email_link_guest');

        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectRepository $repository */
        $repository = $this->createMock('Doctrine\Common\Persistence\ObjectRepository');
        $repository->expects($this->any())
            ->method('findOneBy')
            ->willReturnMap(
                [
                    [['name' => $commonTemplate->getName()], $commonTemplate],
                    [['name' => $guestTemplate->getName()], $guestTemplate],
                ]
            );

        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager $manager */
        $manager = $this->assertManagerCalled($className);
        $manager->expects($this->once())
            ->method('getRepository')
            ->with($className)
            ->willReturn($repository);
    }

    /**
     * @param Quote $quote
     * @param string $email
     * @param string $entityClass
     * @param int $entityId
     * @param string $template
     * @return Email
     */
    protected function createEmailModel(Quote $quote, $email, $entityClass, $entityId, $template)
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName($template);

        $emailModel = new Email();
        $emailModel
            ->setTo([$email])
            ->setEntityClass($entityClass)
            ->setEntityId($entityId)
            ->setContexts([$quote])
            ->setTemplate($emailTemplate);

        return $emailModel;
    }
}

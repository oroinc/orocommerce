<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Notification;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Doctrine\Common\Persistence\ObjectRepository;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EmailBundle\Builder\EmailModelBuilder;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Component\Testing\Unit\EntityTrait;

use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Notification\NotificationHelper;

class NotificationHelperTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const QUOTE_CLASS_NAME = 'Oro\Bundle\SaleBundle\Entity\Quote';
    const EMAIL_TEMPLATE_CLASS_NAME = 'Oro\Bundle\EmailBundle\Entity\EmailTemplate';

    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    protected $registry;

    /** @var Request */
    protected $request;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EmailModelBuilder */
    protected $emailModelBuilder;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Processor */
    protected $emailProcessor;

    /** @var NotificationHelper */
    protected $helper;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->request = new Request();

        $this->emailModelBuilder = $this->getMockBuilder('Oro\Bundle\EmailBundle\Builder\EmailModelBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailProcessor = $this->getMockBuilder('Oro\Bundle\EmailBundle\Mailer\Processor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new NotificationHelper(
            $this->registry,
            $this->request,
            $this->emailModelBuilder,
            $this->emailProcessor
        );
        $this->helper->setQuoteClassName(self::QUOTE_CLASS_NAME);
        $this->helper->setEmailTemplateClassName(self::EMAIL_TEMPLATE_CLASS_NAME);
    }

    protected function tearDown()
    {
        unset($this->helper, $this->registry, $this->request, $this->emailModelBuilder, $this->emailProcessor);
    }

    public function testGetEmailModel()
    {
        $this->emailModelBuilder->expects($this->once())
            ->method('createEmailModel')
            ->willReturn(new Email());

        $accountUser = new AccountUser();
        $accountUser->setEmail('test@example.com');

        /** @var Quote $quote */
        $quote = $this->getEntity(self::QUOTE_CLASS_NAME, ['id' => 42, 'accountUser' => $accountUser]);

        $this->assertRepositoryCalled(self::EMAIL_TEMPLATE_CLASS_NAME);
        $this->assertEquals(
            $this->createEmailModel($quote, 'test@example.com', self::QUOTE_CLASS_NAME, 42),
            $this->helper->getEmailModel($quote)
        );
    }

    /**
     * @dataProvider sendDataProvider
     *
     * @param Quote $quote
     */
    public function testSend(Quote $quote)
    {
        $emailModel = $this->createEmailModel($quote, 'test@example.com', 'stdClass', 42);

        $this->emailProcessor->expects($this->once())->method('process')->with($emailModel);

        if ($quote->isLocked()) {
            $this->registry->expects($this->never())->method($this->anything());
        } else {
            $manager = $this->assertManagerCalled(self::QUOTE_CLASS_NAME);
            $manager->expects($this->once())
                ->method('persist')
                ->with($quote);
            $manager->expects($this->once())
                ->method('flush');
        }

        $this->helper->send($emailModel, $quote);

        $this->assertTrue($quote->isLocked());
    }

    /**
     * @return array
     */
    public function sendDataProvider()
    {
        return [
            'locked quote' => [$this->getEntity(self::QUOTE_CLASS_NAME, ['id' => 42, 'locked' => true])],
            'not locked quote' => [$this->getEntity(self::QUOTE_CLASS_NAME, ['id' => 42, 'locked' => false])]
        ];
    }

    /**
     * @param string $className
     * @return \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected function assertManagerCalled($className)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager $manager */
        $manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

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
        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectRepository $repository */
        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');

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
     * @return Email
     */
    protected function createEmailModel(Quote $quote, $email, $entityClass, $entityId)
    {
        $emailModel = new Email();
        $emailModel
            ->setTo([$email])
            ->setEntityClass($entityClass)
            ->setEntityId($entityId)
            ->setContexts([$quote]);

        return $emailModel;
    }
}

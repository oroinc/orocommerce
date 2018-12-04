<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Helper;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Helper\ContactRequestHelper;
use Oro\Bundle\ContactUsBundle\Entity\ContactReason;
use Oro\Bundle\ContactUsBundle\Entity\ContactRequest;
use Oro\Bundle\ContactUsBundle\Tests\Unit\Stub\ContactReasonStub;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Translation\TranslatorInterface;

class ContactRequestHelperTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $doctrineHelper;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    private $configManager;

    /** @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $localizationHelper;

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $translator;

    /** @var ContactRequestHelper */
    private $helper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->helper = new ContactRequestHelper(
            $this->doctrineHelper,
            $this->configManager,
            $this->localizationHelper,
            $this->translator
        );
    }

    public function testCreateContactRequest()
    {
        $consent = new Consent();
        $consentAcceptance = new ConsentAcceptance();
        $customerUser = new CustomerUser();
        $contactReason = new ContactReasonStub('default titlle');
        $contactRequest = $this->getMockBuilder(ContactRequest::class)
            ->setMethods([
                'setContactReason',
                'setFirstName',
                'setLastName',
                'setEmailAddress',
                'setCustomerUser',
                'setWebsite',
                'setComment',
            ])
            ->getMock();
        $repository = $this->createMock(EntityRepository::class);
        $website = new Website();

        $consentAcceptance->setConsent($consent);

        $consentAcceptance->setCustomerUser($customerUser);
        $customerUser->setFirstName('firstName');
        $customerUser->setLastName('lastName');
        $customerUser->setEmail('email');
        $customerUser->setWebsite($website);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_consent.consent_contact_reason')
            ->willReturn(12);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(ContactReason::class)
            ->willReturn($repository);

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => 12])
            ->willReturn($contactReason);

        $this->doctrineHelper->expects($this->once())
            ->method('createEntityInstance')
            ->with(ContactRequest::class)
            ->willReturn($contactRequest);

        $this->translator->expects($this->once())
            ->method('trans')
            ->willReturn('oro.consent.declined.message');

        $contactRequest->expects($this->once())
            ->method('setContactReason')
            ->with($contactReason);

        $contactRequest->expects($this->once())
            ->method('setContactReason')
            ->with();

        $contactRequest->expects($this->once())
            ->method('setFirstName')
            ->with('firstName');

        $contactRequest->expects($this->once())
            ->method('setLastName')
            ->with('lastName');

        $contactRequest->expects($this->once())
            ->method('setEmailAddress')
            ->with('email');

        $contactRequest->expects($this->once())
            ->method('setCustomerUser')
            ->with($customerUser);

        $contactRequest->expects($this->once())
            ->method('setWebsite')
            ->with($website);

        $contactRequest->expects($this->once())
            ->method('setComment')
            ->with('oro.consent.declined.message');

        $this->assertSame(
            $contactRequest,
            $this->helper->createContactRequest($consentAcceptance)
        );
    }
}

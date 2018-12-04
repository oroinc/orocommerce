<?php

namespace Oro\Bundle\ConsentBundle\Helper;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConsentBundle\DependencyInjection\Configuration;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ContactUsBundle\Entity\ContactReason;
use Oro\Bundle\ContactUsBundle\Entity\ContactRequest;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * This helper creates contact request for consent that was unchecked
 */
class ContactRequestHelper
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager $configManager
     * @param LocalizationHelper $localizationHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager,
        LocalizationHelper $localizationHelper,
        TranslatorInterface $translator
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
        $this->localizationHelper = $localizationHelper;
        $this->translator = $translator;
    }

    /**
     * @param ConsentAcceptance $acceptance
     *
     * @return ContactRequest
     */
    public function createContactRequest(ConsentAcceptance $acceptance)
    {
        $customerUser = $acceptance->getCustomerUser();
        $contactReason = $this->getContactReason();

        /** @var ContactRequest $contactRequest */
        $contactRequest = $this->doctrineHelper->createEntityInstance(ContactRequest::class);
        $contactRequest->setContactReason($contactReason);
        $contactRequest->setFirstName($customerUser->getFirstName());
        $contactRequest->setLastName($customerUser->getLastName());
        $contactRequest->setEmailAddress($customerUser->getEmail());
        $contactRequest->setCustomerUser($customerUser);
        if (method_exists($contactRequest, 'setWebsite')) {
            $contactRequest->setWebsite($customerUser->getWebsite());
        }

        $comment = $this->prepareComment($acceptance->getConsent());
        $contactRequest->setComment($comment);

        return $contactRequest;
    }

    /**
     * @param Consent $consent
     *
     * @return string
     */
    protected function prepareComment(Consent $consent)
    {
        $comment = $this->translator->trans(
            'oro.consent.declined.message',
            ['%consent%' => $this->localizationHelper->getLocalizedValue($consent->getNames())]
        );

        return $comment;
    }

    /**
     * @return ContactReason
     */
    private function getContactReason()
    {
        $configKey = Configuration::getConfigKey(Configuration::CONSENT_CONTACT_REASON);
        $contactReasonId = $this->configManager->get($configKey);
        /** @var ContactReason $contactReason */
        $contactReason = $this->doctrineHelper->getEntityRepository(ContactReason::class)
            ->findOneBy(['id' => $contactReasonId]);

        return $contactReason;
    }
}

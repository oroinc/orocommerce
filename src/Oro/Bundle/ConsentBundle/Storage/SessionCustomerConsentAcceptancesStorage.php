<?php

namespace Oro\Bundle\ConsentBundle\Storage;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Implementation of AbstractCustomerConsentAcceptancesStorage using session as a storage
 */
class SessionCustomerConsentAcceptancesStorage extends AbstractCustomerConsentAcceptancesStorage
{
    /** @var SessionInterface */
    private $session;

    /**
     * {@inheritdoc}
     */
    protected function saveToStorage($consentAcceptancesAttributes)
    {
        $this->session->set(
            self::GUEST_CUSTOMER_CONSENT_ACCEPTANCES_KEY,
            json_encode($consentAcceptancesAttributes)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getFromStorage()
    {
        $consentAcceptancesAttributesEncoded = $this->session->get(
            self::GUEST_CUSTOMER_CONSENT_ACCEPTANCES_KEY,
            false
        );

        if (false === $consentAcceptancesAttributesEncoded) {
            return [];
        }

        $consentAcceptancesAttributes = json_decode($consentAcceptancesAttributesEncoded, true);

        if (!is_array($consentAcceptancesAttributes)) {
            return [];
        }

        return $consentAcceptancesAttributes;
    }

    /**
     * {@inheritdoc}
     */
    public function clearData()
    {
        $this->session->remove(self::GUEST_CUSTOMER_CONSENT_ACCEPTANCES_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function hasData()
    {
        return $this->session->has(self::GUEST_CUSTOMER_CONSENT_ACCEPTANCES_KEY);
    }

    /**
     * @param SessionInterface $storage
     */
    public function setStorage(SessionInterface $storage)
    {
        $this->session = $storage;
    }
}

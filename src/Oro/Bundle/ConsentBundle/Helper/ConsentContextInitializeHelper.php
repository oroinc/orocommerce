<?php

namespace Oro\Bundle\ConsentBundle\Helper;

use Oro\Bundle\ConsentBundle\Provider\ConsentContextProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

/**
 * Logic that processes consent context initialization with additional checking
 * on possibility to resolve context website
 */
class ConsentContextInitializeHelper implements ConsentContextInitializeHelperInterface
{
    /**
     * @var ConsentContextProvider
     */
    private $consentContextProvider;

    /**
     * @var WebsiteManager
     */
    private $websiteManager;

    /**
     * @param ConsentContextProvider $consentContextProvider
     * @param WebsiteManager $websiteManager
     */
    public function __construct(
        ConsentContextProvider $consentContextProvider,
        WebsiteManager $websiteManager
    ) {
        $this->consentContextProvider = $consentContextProvider;
        $this->websiteManager = $websiteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(CustomerUser $customerUser = null, $force = false)
    {
        if ($this->consentContextProvider->isInitialized() && false === $force) {
            return true;
        } elseif ($this->consentContextProvider->isInitialized()) {
            $this->consentContextProvider->resetContext();
        }

        $contextWebsite = $this->guessContextWebsite($customerUser);
        /**
         * Initializing of context is forbidden in case we can't resolve the website context
         */
        if ($contextWebsite instanceof Website) {
            $this->consentContextProvider->initializeContext(
                $contextWebsite,
                $customerUser
            );

            return true;
        }

        return false;
    }

    /**
     * @param CustomerUser|null $customerUser
     *
     * @return Website|null
     */
    private function guessContextWebsite(CustomerUser $customerUser = null)
    {
        if ($customerUser && $customerUser->getWebsite()) {
            return $customerUser->getWebsite();
        }

        return $this->websiteManager->getCurrentWebsite();
    }
}

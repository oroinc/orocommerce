<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle\Test;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

class FrontendWebTestCase extends WebTestCase
{
    /**
     * @var WebsiteManager
     */
    protected $storedWebsiteManager;

    protected function initClient(array $options = [], array $server = [], $force = false)
    {
        $client = parent::initClient($options, $server, $force);

        if (!$this->storedWebsiteManager) {
            $this->storedWebsiteManager = $this->client->getContainer()->get('orob2b_website.manager');
        }

        return $client;
    }

    protected function tearDown()
    {
        if ($this->storedWebsiteManager) {
            $this->client->getContainer()->set('orob2b_website.manager', $this->storedWebsiteManager);
            unset($this->storedWebsiteManager);
        }

        parent::tearDown();
    }

    /**
     * @param string $websiteReference
     */
    public function setCurrentWebsite($websiteReference = null)
    {
        $defaultWebsite = $this->storedWebsiteManager->getDefaultWebsite();
        if (!$websiteReference || $websiteReference === 'default') {
            $website = $defaultWebsite;
        } else {
            if (!$this->hasReference($websiteReference)) {
                throw new \RuntimeException(
                    sprintf('WebsiteScope scope reference "%s" was not found', $websiteReference)
                );
            }
            $website = $this->getReference($websiteReference);
        }
        $managerMock = $this->getMockBuilder(WebsiteManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $managerMock->expects($this->any())
            ->method('getCurrentWebsite')
            ->willReturn($website);
        $managerMock->expects($this->any())
            ->method('getDefaultWebsite')
            ->willReturn($defaultWebsite);
        $this->client->getContainer()->set('orob2b_website.manager', $managerMock);
    }
}

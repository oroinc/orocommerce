<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle\Test;

use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\WebsiteBundle\Tests\Functional\Stub\WebsiteManagerStub;

abstract class FrontendWebTestCase extends WebTestCase
{
    /**
     * @var WebsiteManager
     */
    protected $storedWebsiteManager;

    /**
     * {@inheritdoc}
     */
    protected function initClient(array $options = [], array $server = [], $force = false)
    {
        $client = parent::initClient($options, $server, $force);

        if (!$this->storedWebsiteManager) {
            $this->storedWebsiteManager = $this->client->getContainer()->get('oro_website.manager');
        }

        return $client;
    }

    protected function tearDown()
    {
        if ($this->storedWebsiteManager) {
            $this->client->getContainer()->set('oro_website.manager', $this->storedWebsiteManager);
            unset($this->storedWebsiteManager);
        }

        parent::tearDown();
    }

    /**
     * @param string $email
     */
    protected function updateCustomerUserSecurityToken($email)
    {
        $user = $this->getContainer()
            ->get('doctrine')
            ->getRepository(CustomerUser::class)
            ->findOneBy(['email' => $email]);

        $token = new UsernamePasswordOrganizationToken($user, false, 'k', $user->getOrganization(), $user->getRoles());
        $this->getContainer()->get('security.token_storage')->setToken($token);
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

        $managerStub = new WebsiteManagerStub($website, $defaultWebsite);
        $this->client->getContainer()->set('oro_website.manager', $managerStub);
    }
}

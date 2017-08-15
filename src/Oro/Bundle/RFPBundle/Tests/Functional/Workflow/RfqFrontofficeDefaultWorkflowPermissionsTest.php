<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Workflow;

use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadUserData;

class RfqFrontofficeDefaultWorkflowPermissionsTest extends AbstractRfqFrontofficeDefaultWorkflowTest
{
    public function testCancelTransitionWithoutPermissions()
    {
        $crawler = $this->openEntityViewPage($this->request);
        $link = $this->getTransitionLink(
            $crawler,
            $this->getTransitionLinkId($this->getWorkflowName(), 'cancel_transition')
        );
        $this->assertEmpty($link, 'Transition button must not be available');
    }

    /**
     * {@inheritdoc}
     */
    protected function getWorkflowName()
    {
        return 'b2b_rfq_frontoffice_default';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomerUserEmail()
    {
        return LoadUserData::ACCOUNT1_USER2;
    }

    /**
     * {@inheritdoc}
     */
    protected function getBasicAuthHeader()
    {
        return self::generateBasicAuthHeader(LoadUserData::ACCOUNT1_USER2, LoadUserData::ACCOUNT1_USER2);
    }

    /**
     * {@inheritdoc}
     */
    protected function getWsseAuthHeader()
    {
        return self::generateWsseAuthHeader(LoadUserData::ACCOUNT1_USER2, LoadUserData::ACCOUNT1_USER2);
    }
}

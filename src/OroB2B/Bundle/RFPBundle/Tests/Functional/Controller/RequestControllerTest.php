<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Controller;

use OroB2B\Bundle\FrontendBundle\Test\WebTestCase;

class RequestControllerTest extends WebTestCase
{
    const REQUEST_FIRST_NAME    = 'Agnetha';
    const REQUEST_LAST_NAME     = 'Faltskog';
    const REQUEST_EMAIL         = 'contact@agnetha.com';
    const REQUEST_INVALID_EMAIL = 'No No No, David Blaine, No';
    const REQUEST_PHONE         = '+38 (044) 494-42-70';
    const REQUEST_COMPANY       = 'ABBA';
    const REQUEST_ROLE          = 'Singer';
    const REQUEST_BODY          = 'Gimme gimme gimme a man after midnight';

    const REQUEST_NOTIFICATION_SUBJECT_PARTIAL = 'New RFP from';
    const REQUEST_NOTIFICATION_BODY_PARTIAL    = 'created new RFP';

    const REQUEST_SUBMIT_BTN  = 'Submit Request For Proposal';
    const REQUEST_SAVED_MSG   = 'Your Request For Proposal successfully saved';
    const REQUEST_INVALID_MSG = 'This value is not a valid email address';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
    }

    /**
     * Test valid submit
     */
    public function testSubmit()
    {
        // Test if form was successfully submitted
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_rfp_request_create'));

        $form = $crawler->selectButton(self::REQUEST_SUBMIT_BTN)->form(array(
            'orob2b_rfp_request_type[firstName]' => self::REQUEST_FIRST_NAME,
            'orob2b_rfp_request_type[lastName]'  => self::REQUEST_LAST_NAME,
            'orob2b_rfp_request_type[email]'     => self::REQUEST_EMAIL,
            'orob2b_rfp_request_type[phone]'     => self::REQUEST_PHONE,
            'orob2b_rfp_request_type[company]'   => self::REQUEST_COMPANY,
            'orob2b_rfp_request_type[role]'      => self::REQUEST_ROLE,
            'orob2b_rfp_request_type[body]'      => self::REQUEST_BODY,
        ));

        $this->client->submit($form);

        // Collect messages for future needs
        /** @var array $collectedMessages */
        $collectedMessages = $this->client->getProfile()->getCollector('swiftmailer')->getMessages();

        $crawler = $this->client->followRedirect();

        $this->assertContains(self::REQUEST_SAVED_MSG, $crawler->html());

        // Test if entity was created with default status
        $em = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroB2BRFPBundle:Request');

        $originalRequest = $em->getRepository('OroB2BRFPBundle:Request')
            ->findOneBy([
                'firstName' => self::REQUEST_FIRST_NAME,
                'lastName'  => self::REQUEST_LAST_NAME,
                'email'     => self::REQUEST_EMAIL,
                'phone'     => self::REQUEST_PHONE,
                'company'   => self::REQUEST_COMPANY,
                'role'      => self::REQUEST_ROLE,
                'body'      => self::REQUEST_BODY
            ]);

        $this->assertInstanceOf('OroB2B\Bundle\RFPBundle\Entity\Request', $originalRequest);

        // Cleaning
        $request = clone $originalRequest;
        $em->remove($originalRequest);
        $em->flush();

        $defaultRequestStatusName = $this->getContainer()
            ->get('oro_application.config_manager')
            ->get('oro_b2b_rfp_admin.default_request_status'); // expects open

        $this->assertInstanceOf('OroB2B\Bundle\RFPBundle\Entity\RequestStatus', $request->getStatus());

        $this->assertEquals($defaultRequestStatusName, $request->getStatus()->getName());

        // Test email notification
        $defaultUserForNotificationEmail = $this->getContainer()
            ->get('oro_application.config_manager')
            ->get('oro_b2b_rfp_admin.default_user_for_notifications'); // expects admin@example.com

        /** @var \Swift_Message $message */
        $message = reset($collectedMessages);

        // Asserting e-mail data
        $this->assertInstanceOf('Swift_Message', $message);

        $this->assertEquals($defaultUserForNotificationEmail, key($message->getTo()));
        $this->assertEquals($defaultUserForNotificationEmail, key($message->getFrom()));

        $this->assertContains(self::REQUEST_NOTIFICATION_SUBJECT_PARTIAL, $message->getSubject());
        $this->assertContains(self::REQUEST_FIRST_NAME, $message->getSubject());
        $this->assertContains(self::REQUEST_LAST_NAME, $message->getSubject());

        $this->assertContains(self::REQUEST_NOTIFICATION_BODY_PARTIAL, $message->getBody());
        $this->assertContains(self::REQUEST_FIRST_NAME, $message->getBody());
        $this->assertContains(self::REQUEST_LAST_NAME, $message->getBody());
        $this->assertContains(self::REQUEST_EMAIL, $message->getBody());
        $this->assertContains(self::REQUEST_PHONE, $message->getBody());
        $this->assertContains(self::REQUEST_COMPANY, $message->getBody());
        $this->assertContains(self::REQUEST_ROLE, $message->getBody());
    }

    /**
     * Test invalid submit
     */
    public function testInvalidSubmit()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_rfp_request_create'));

        $form = $crawler->selectButton(self::REQUEST_SUBMIT_BTN)->form(array(
            'orob2b_rfp_request_type[firstName]' => self::REQUEST_FIRST_NAME,
            'orob2b_rfp_request_type[lastName]'  => self::REQUEST_LAST_NAME,
            'orob2b_rfp_request_type[email]'     => self::REQUEST_INVALID_EMAIL,
            'orob2b_rfp_request_type[phone]'     => self::REQUEST_PHONE,
            'orob2b_rfp_request_type[company]'   => self::REQUEST_COMPANY,
            'orob2b_rfp_request_type[role]'      => self::REQUEST_ROLE,
            'orob2b_rfp_request_type[body]'      => self::REQUEST_BODY,
        ));

        $this->client->submit($form);

        $this->assertContains(self::REQUEST_INVALID_MSG, $this->client->getCrawler()->html());
    }
}

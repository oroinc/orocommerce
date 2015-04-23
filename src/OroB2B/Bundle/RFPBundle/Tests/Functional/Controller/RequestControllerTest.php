<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Controller;

use OroB2B\Bundle\FrontendBundle\Test\WebTestCase;

class RequstControllerTest extends WebTestCase
{
    const REQUEST_FIRST_NAME = 'Agnetha';
    const REQUEST_LAST_NAME  = 'Faltskog';
    const REQUEST_EMAIL      = 'contact@agnetha.com';
    const REQUEST_PHONE      = '+38 (044) 494-42-70';
    const REQUEST_COMPANY    = 'ABBA';
    const REQUEST_ROLE       = 'Singer';
    const REQUEST_BODY       = 'Gimme gimme gimme a man after midnight';

    const REQUEST_SUBMIT_BTN = 'Submit Request For Proposal';
    const REQUEST_SAVED_MSG  = 'Your Request For Proposal successfully saved!';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
    }

    public function testSubmit()
    {
        // PART 1: Test if form was successfully submitted
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

        $crawler = $this->client->followRedirect();

        $this->assertContains(self::REQUEST_SAVED_MSG, $crawler->html());

        // PART 2: Test if entity was created with default status
        $em = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroB2BRFPBundle:Request');

        $request = $em->getRepository('OroB2BRFPBundle:Request')
            ->findOneBy([
                'firstName' => self::REQUEST_FIRST_NAME,
                'lastName'  => self::REQUEST_LAST_NAME,
                'email'     => self::REQUEST_EMAIL,
                'phone'     => self::REQUEST_PHONE,
                'company'   => self::REQUEST_COMPANY,
                'role'      => self::REQUEST_ROLE,
                'body'      => self::REQUEST_BODY
            ]);

        $this->assertInstanceOf('OroB2B\Bundle\RFPBundle\Entity\Request', $request);

        $defaultRequestStatusName = $this->getContainer()
            ->get('oro_config.fake_manager') // TODO: rename to oro_application.config_manager
            ->get('oro_b2b_rfp_admin.default_request_status');

        $this->assertInstanceOf('OroB2B\Bundle\RFPBundle\Entity\RequestStatus', $request->getStatus());

        $this->assertEquals($defaultRequestStatusName, $request->getStatus()->getName());

        // PART 3: Test email notification
        // TODO: wait until BB-406 will be implemented

        // PART 4: Cleaning
        $em->remove($request);
        $em->flush();
    }
}

<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Entity;

use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RedirectTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testFromHash()
    {
        $from = '/from';

        $redirect = new Redirect();
        $redirect->setFrom($from);
        $redirect->setTo('/to');
        $redirect->setType(Redirect::MOVED_PERMANENTLY);

        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Redirect::class);
        $em->persist($redirect);

        $this->assertAttributeEquals(md5($from), 'fromHash', $redirect);

        $em->flush();

        $updatedFrom = '/new-page';
        $redirect->setFrom($updatedFrom);

        $em->flush();

        $this->assertAttributeEquals(md5($updatedFrom), 'fromHash', $redirect);

    }
}

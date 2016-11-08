<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Entity;

use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class SlugTest extends WebTestCase
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
        $url = '/url';
        $slug = new Slug();
        $slug->setUrl($url);

        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Slug::class);
        $em->persist($slug);

        $this->assertAttributeEquals(md5($url), 'urlHash', $slug);
        $em->flush();

        $updatedUrl = '/new-url';
        $slug->setUrl($updatedUrl);
        $em->flush();
        $this->assertAttributeEquals(md5($updatedUrl), 'urlHash', $slug);
    }
}

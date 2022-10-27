<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;

class LandingPageForVisitorTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableVisitor();
        $this->loadFixtures([
            '@OroCMSBundle/Tests/Functional/Api/Frontend/DataFixtures/landing_page.yml'
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'landingpages'],
            ['filter' => ['id' => ['gte' => '<toString(@page1->id)>']]]
        );

        $this->assertResponseContains('cget_landing_page.yml', $response);
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'landingpages', 'id' => '<toString(@page1->id)>']
        );

        $this->assertResponseContains('get_landing_page.yml', $response);
    }
}

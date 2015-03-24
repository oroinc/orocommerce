<?php

namespace OroB2B\Bundle\RedirectBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use FOS\RestBundle\Util\Codes;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class RedirectControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
    }

    /**
     * Test slugify
     * @dataProvider slugifyActionDataProvider
     */
    public function testSlugifyAction($string, $slug)
    {
        $this->client->request(
            'GET',
            $this->getUrl('orob2b_api_slugify_slug', ['string' => $string])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), Codes::HTTP_OK);

        $this->assertNotEmpty($result);
        $this->assertEquals($slug, $result['slug']);
    }

    /**
     * @return array
     */
    public function slugifyActionDataProvider()
    {
        return [
            [
                'string' => 'hello.',
                'slug' => 'hello'
            ],
            [
                'string' => 'LONDON - Cambridge!',
                'slug' => 'london-cambridge'
            ],
            [
                'string' => 'Ça ne fait rien.',
                'slug' => 'ca-ne-fait-rien'
            ],
            [
                'string' => 'Lo siento, no hablo espanol...',
                'slug' => 'lo-siento-no-hablo-espanol'
            ],
            [
                'string' => 'Знает ли он русский?',
                'slug' => 'znaet-li-on-russkij'
            ],
            [
                'string' => 'Слов\'янське слово «Україна»',
                'slug' => 'slovanske-slovo-ukraina'
            ],
            [
                'string' => '火を見るより明らかだ',
                'slug' => 'huowo-jianruyori-mingrakada'
            ],
            [
                'string' => 'Alle Gewässer fließen ins Meer',
                'slug' => 'alle-gewasser-flieen-ins-meer'
            ],
            [
                'string' => '断鹤续凫',
                'slug' => 'duan-he-xu-fu'
            ],
            [
                'string' => 'ძმასთან მიმავალი გზა მუდამ მოკლეაო',
                'slug' => 'dzmastan-mimavali-gza-mudam-mokleao'
            ],
            [
                'string' => 'Czyja siła, tego prawda',
                'slug' => 'czyja-sia-tego-prawda'
            ]
        ];
    }
}

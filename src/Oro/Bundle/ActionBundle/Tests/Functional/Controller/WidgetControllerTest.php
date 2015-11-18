<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Controller;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;
use Oro\Bundle\CacheBundle\Provider\FilesystemCache;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class WidgetControllerTest extends WebTestCase
{
    /** @var FilesystemCache */
    protected $cacheProvider;

    protected function setUp()
    {
        $this->initClient();

        $this->cacheProvider = $this->getContainer()->get('oro_action.cache.provider');
    }

    protected function tearDown()
    {
        $this->cacheProvider->delete(ActionConfigurationProvider::ROOT_NODE_NAME);
    }

    /**
     * @dataProvider buttonsActionDataProvider
     *
     * @param array $config
     * @param $route
     * @param $entityId
     * @param $entityClass
     * @param $expected
     */
    public function testButtonsActionForRoutes(array $config, $route, $entityId, $entityClass, $expected)
    {
        $this->cacheProvider->save(ActionConfigurationProvider::ROOT_NODE_NAME, $config);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_action_widget_buttons',
                [
                    '_widgetContainer' => 'dialog',
                    'route' => $route,
                    'entityId' => $entityId,
                    'entityClass' => $entityClass,
                ]
            )
        );

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        if ($expected) {
            $this->assertContains($expected, $crawler->html());
        } else {
            $this->assertEmpty($crawler);
        }
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buttonsActionDataProvider()
    {
        $label = 'oro.action.test.label';

        $config = [
            'oro_action_test_action' => [
                'label' => $label,
                'enabled' => true,
                'order' => 10,
                'applications' => ['backend', 'frontend'],
                'frontend_options' => [],
                'entities' => [],
                'routes' => []
            ]
        ];

        return [
            'existing entity' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_action' => ['entities' => ['Oro\Bundle\TestFrameworkBundle\Entity\Product']]]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => 42,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\Product',
                'expected' => $label
            ],
            'existing entity short syntax' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_action' => ['entities' => ['OroTestFrameworkBundle:Product']]]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => 42,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\Product',
                'expected' => $label
            ],
            'existing entity with root namespace' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_action' => ['entities' => ['\Oro\Bundle\TestFrameworkBundle\Entity\Product']]]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => 42,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\Product',
                'expected' => $label
            ],
            'unknown entity' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_action' => ['entities' => ['Oro\Bundle\TestFrameworkBundle\Enti\UnknownEntity']]]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => 42,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\Product',
                'expected' => false
            ],
            'unknown entity short syntax' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_action' => ['entities' => ['OroTestFrameworkBundle:UnknownEntity']]]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => 42,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\Product',
                'expected' => false
            ],
            'existing route' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_action' => ['routes' => ['oro_action_test_route']]]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => 42,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\Product',
                'expected' => $label
            ],
            'unknown route' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_action' => ['routes' => ['oro_action_unknown_route']]]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => 42,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\Product',
                'expected' => false
            ],
            'empty context' => [
                'config' => $config,
                'route' => 'oro_action_test_route',
                'entityId' => 42,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\Product',
                'expected' => false
            ],
            'existing route and entity' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_action' =>
                        [
                            'entities' => ['Oro\Bundle\TestFrameworkBundle\Entity\Product'],
                            'routes' => ['oro_action_test_route']
                        ]
                    ]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => null,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\Product',
                'expected' => $label
            ]
        ];
    }
}

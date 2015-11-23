<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;
use Oro\Bundle\ActionBundle\Tests\Functional\DataFixtures\LoadTestEntityData;
use Oro\Bundle\CacheBundle\Provider\FilesystemCache;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ActionControllerTest extends WebTestCase
{
    const MESSAGE_DEFAULT = 'test message';

    const MESSAGE_NEW = 'new test message';

    /** @var TestActivity */
    private $entity;

    /** @var int */
    private $entityId;

    /** @var FilesystemCache */
    protected $cacheProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], static::generateWsseAuthHeader());

        $this->cacheProvider = static::getContainer()->get('oro_action.cache.provider');
        $this->loadFixtures([
            'Oro\Bundle\ActionBundle\Tests\Functional\DataFixtures\LoadTestEntityData',
        ]);
        $this->entity = $this->getReference(LoadTestEntityData::TEST_ENTITY_1)
            ->setMessage(self::MESSAGE_DEFAULT);
        $this->entityId = $this->entity->getId();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->cacheProvider->delete(ActionConfigurationProvider::ROOT_NODE_NAME);
    }

    /**
     * @dataProvider executeActionDataProvider
     *
     * @param array $config
     * @param string $route
     * @param bool|int $entityId
     * @param string $entityClass
     * @param int $statusCode
     * @param bool|string $updated
     */
    public function testExecuteAction(array $config, $route, $entityId, $entityClass, $statusCode, $updated)
    {
        $this->cacheProvider->save(ActionConfigurationProvider::ROOT_NODE_NAME, $config);

        if ($entityId) {
            $entityId = $this->entityId;
        }

        static::assertEquals(self::MESSAGE_DEFAULT, $this->entity->getMessage());

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_action_execute',
                [
                    'actionName' => 'oro_action_test_action',
                    'route' => $route,
                    'entityId' => $entityId,
                    'entityClass' => $entityClass,
                ]
            )
        );

        $result = $this->client->getResponse();

        static::assertEquals($updated ? self::MESSAGE_NEW : self::MESSAGE_DEFAULT, $this->entity->getMessage());
        static::assertResponseStatusCodeEquals($result, $statusCode);
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function executeActionDataProvider()
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
                'routes' => [],
                'postfunctions' => [['@assign_value' => ['$message', 'new test message']]],
            ]
        ];

        return [
            'existing entity right conditions' => [
                'config' => array_merge_recursive(
                    $config,
                    [
                        'oro_action_test_action' => [
                            'entities' => ['Oro\Bundle\TestFrameworkBundle\Entity\TestActivity'],
                            'preconditions' => ['@equal' => ['$message', self::MESSAGE_DEFAULT]],
                        ],
                    ]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'statusCode' => 200,
                'updated' => true,
            ],
            'existing entity wrong conditions' => [
                'config' => array_merge_recursive(
                    $config,
                    [
                        'oro_action_test_action' => [
                            'entities' => ['Oro\Bundle\TestFrameworkBundle\Entity\TestActivity'],
                            'preconditions' => ['@equal' => ['$message', 'test message wrong']],
                        ],
                    ]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'statusCode' => 404,
                'updated' => false,
            ],
            'existing entity short syntax' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_action' => ['entities' => ['OroTestFrameworkBundle:TestActivity']]]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'statusCode' => 200,
                'updated' => false,
            ],
            'existing entity with root namespace' => [
                'config' => array_merge_recursive(
                    $config,
                    [
                        'oro_action_test_action' => [
                            'entities' => ['\Oro\Bundle\TestFrameworkBundle\Entity\TestActivity']
                        ]
                    ]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'statusCode' => 200,
                'updated' => false,
            ],
            'unknown entity' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_action' => ['entities' => ['Oro\Bundle\TestFrameworkBundle\Enti\UnknownEntity']]]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'statusCode' => 404,
                'updated' => false,
            ],
            'unknown entity short syntax' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_action' => ['entities' => ['OroTestFrameworkBundle:UnknownEntity']]]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'statusCode' => 404,
                'updated' => false,
            ],
            'existing route' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_action' => ['routes' => ['oro_action_test_route']]]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'statusCode' => 200,
                'updated' => false,
            ],
            'unknown route' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_action' => ['routes' => ['oro_action_unknown_route']]]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => false,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'statusCode' => 404,
                'updated' => false,
            ],
            'empty context' => [
                'config' => $config,
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'statusCode' => 404,
                'updated' => false,
            ],
            'existing route and entity' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_action' =>
                        [
                            'entities' => ['Oro\Bundle\TestFrameworkBundle\Entity\TestActivity'],
                            'routes' => ['oro_action_test_route']
                        ]
                    ]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => null,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'statusCode' => 200,
                'updated' => false,
            ]
        ];
    }
}

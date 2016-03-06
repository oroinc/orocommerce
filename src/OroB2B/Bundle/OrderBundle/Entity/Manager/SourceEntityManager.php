<?php

namespace OroB2B\Bundle\OrderBundle\Entity\Manager;

use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;

use OroB2B\Bundle\OrderBundle\Entity\Order;

class SourceEntityManager
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var RouterInterface */
    protected $router;

    /** @var EntityClassNameHelper */
    protected $entityClassNameHelper;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param ConfigManager $configManager
     * @param RouterInterface $router
     * @param DoctrineHelper $doctrineHelper
     * @param EntityClassNameHelper $entityClassNameHelper
     */
    public function __construct(
        ConfigManager $configManager,
        RouterInterface $router,
        DoctrineHelper $doctrineHelper,
        EntityClassNameHelper $entityClassNameHelper
    ) {
        $this->configManager        = $configManager;
        $this->router               = $router;
        $this->doctrineHelper       = $doctrineHelper;
        $this->entityClassNameHelper = $entityClassNameHelper;
    }

    /**
     * @param Order $order
     *
     * @return null|string
     */
    public function getSourceEntityLink(Order $order)
    {
        $sourceEntityClass = $order->getSourceEntityClass();
        $sourceEntityId = $order->getSourceEntityId();

        $metadata = $this->configManager->getEntityMetadata($sourceEntityClass);
        $link     = null;
        if ($metadata) {
            try {
                $route = $metadata->getRoute('view', true);
            } catch (\LogicException $exception) {
                // Need for cases when entity does not have route.
                return null;
            }
            $link = $this->router->generate($route, ['id' => $sourceEntityId]);
        } elseif (ExtendHelper::isCustomEntity($sourceEntityClass)) {
            $safeClassName = $this->entityClassNameHelper->getUrlSafeClassName($sourceEntityClass);
            // Generate view link for the custom entity
            $link = $this->router->generate(
                'oro_entity_view',
                [
                    'id'         => $sourceEntityId,
                    'entityName' => $safeClassName

                ]
            );
        }

        return $link;
    }

    /**
     * @param Order $order
     *
     * @return null|object
     */
    public function getSourceEntity(Order $order)
    {
        return $this->doctrineHelper->getEntity(
            $order->getSourceEntityClass(),
            $order->getSourceEntityId()
        );
    }
}

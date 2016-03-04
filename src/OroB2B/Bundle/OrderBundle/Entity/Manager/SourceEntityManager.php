<?php

namespace OroB2B\Bundle\OrderBundle\Entity\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\ActivityBundle\Event\PrepareContextTitleEvent;

class SourceEntityManager //extends ApiEntityManager
{
//    /** @var ActivityManager */
//    protected $activityManager;
//
//    /** @var TokenStorageInterface */
//    protected $securityTokenStorage;

    /** @var ConfigManager */
    protected $configManager;

    /** @var RouterInterface */
    protected $router;

//    /** @var EntityAliasResolver */
//    protected $entityAliasResolver;
//
//    /** @var ObjectMapper */
//    protected $mapper;
//
//    /** @var TranslatorInterface */
//    protected $translator;
//
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param ObjectManager         $om
     * @param ActivityManager       $activityManager
     * @param TokenStorageInterface $securityTokenStorage
     * @param ConfigManager         $configManager
     * @param RouterInterface       $router
     * @param EntityAliasResolver   $entityAliasResolver
     * @param ObjectMapper          $objectMapper
     * @param TranslatorInterface   $translator
     * @param DoctrineHelper        $doctrineHelper
     */
    public function __construct(
//        ObjectManager $om,
//        ActivityManager $activityManager,
//        TokenStorageInterface $securityTokenStorage,
        ConfigManager $configManager,
        RouterInterface $router,
//        EntityAliasResolver $entityAliasResolver,
//        ObjectMapper $objectMapper,
//        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper
    ) {
       // parent::__construct(null, $om);

//        $this->activityManager      = $activityManager;
//        $this->securityTokenStorage = $securityTokenStorage;
        $this->configManager        = $configManager;
        $this->router               = $router;
//        $this->entityAliasResolver  = $entityAliasResolver;
//        $this->mapper               = $objectMapper;
//        $this->translator           = $translator;
        $this->doctrineHelper       = $doctrineHelper;
    }

    /**
     * @param string $targetClass The FQCN of the activity target entity
     * @param int    $targetId    The identifier of the activity target entity
     *
     * @return string|null
     */
    public function getSourceEntityLink($targetClass, $targetId)
    {
        $metadata = $this->configManager->getEntityMetadata($targetClass);
        $link     = null;
        if ($metadata) {
            try {
                $route = $metadata->getRoute('view', true);
            } catch (\LogicException $exception) {
                // Need for cases when entity does not have route.
                return null;
            }
            $link = $this->router->generate($route, ['id' => $targetId]);
        } elseif (ExtendHelper::isCustomEntity($targetClass)) {
            $safeClassName = $this->entityClassNameHelper->getUrlSafeClassName($targetClass);
            // Generate view link for the custom entity
            $link = $this->router->generate(
                'oro_entity_view',
                [
                    'id'         => $targetId,
                    'entityName' => $safeClassName

                ]
            );
        }

        return $link;
    }

//    /**
//     * @param $item
//     * @param $targetClass
//     * @param $target
//     * @param $targetId
//     *
//     * @return mixed
//     */
//    public function prepareItemTitle($item, $targetClass, $target, $targetId)
//    {
//        if (!array_key_exists('title', $item) || !$item['title']) {
//            if ($fields = $this->mapper->getEntityMapParameter($targetClass, 'title_fields')) {
//                $text = [];
//                foreach ($fields as $field) {
//                    $text[] = $this->mapper->getFieldValue($target, $field);
//                }
//                $item['title'] = implode(' ', $text);
//                return $item;
//            } else {
//                $item['title'] = $this->translator->trans('oro.entity.item', ['%id%' => $targetId]);
//                return $item;
//            }
//        }
//        return $item;
//    }
}

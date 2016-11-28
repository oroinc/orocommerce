<?php

namespace Oro\Bundle\CustomerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\CustomerBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;

class AclPermissionController extends Controller
{
    /**
     * @Route(
     *  "/acl-access-levels/{oid}/{permission}",
     *  name="oro_account_acl_access_levels",
     *  requirements={"oid"="[\w]+:[\w\:\(\)]+", "permission"="[\w/]+"},
     *  defaults={"_format"="json", "permission"=null}
     * )
     * @Template
     *
     * @param string $oid
     * @param string $permission
     *
     * @return array
     */
    public function aclAccessLevelsAction($oid, $permission = null)
    {
        if (strpos($oid, 'entity:') === 0) {
            $oid = 'entity:' . $this->get('oro_entity.routing_helper')->resolveEntityClass(substr($oid, 7));
        }

        $chainMetadataProvider = $this->container->get('oro_security.owner.metadata_provider.chain');
        $chainMetadataProvider->startProviderEmulation(FrontendOwnershipMetadataProvider::ALIAS);

        $levels = $this
            ->get('oro_security.acl.manager')
            ->getAccessLevels($oid, $permission);

        $chainMetadataProvider->stopProviderEmulation();

        $prefixResolver = $this->get('oro_customer.acl.resolver.role_translation_prefix');

        return [
            'levels' => $levels,
            'roleTranslationPrefix' => $prefixResolver->getPrefix()
        ];
    }
}

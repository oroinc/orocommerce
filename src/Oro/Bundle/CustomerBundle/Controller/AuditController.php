<?php

namespace Oro\Bundle\CustomerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

class AuditController extends Controller
{
    /**
     * @Route(
     *      "/history/{entity}/{id}/{_format}",
     *      name="oro_account_frontend_dataaudit_history",
     *      requirements={"entity"="[a-zA-Z0-9_]+", "id"="\d+"},
     *      defaults={"entity"="entity", "id"=0, "_format" = "html"}
     * )
     * @Template("OroDataAuditBundle:Audit/widget:history.html.twig")
     * @Acl(
     *      id="oro_account_dataaudit_history",
     *      type="action",
     *      label="oro.account.dataaudit.module_label",
     *      group_name="commerce"
     * )
     * @param string $entity
     * @param string $id
     * @return array
     */
    public function historyAction($entity, $id)
    {
        return [
            'gridName' => 'frontend-audit-history-grid',
            'entityClass' => $entity,
            'entityId' => $id,
        ];
    }
}

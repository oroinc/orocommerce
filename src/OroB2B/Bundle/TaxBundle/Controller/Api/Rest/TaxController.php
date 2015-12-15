<?php

namespace OroB2B\Bundle\TaxBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Routing\ClassResourceInterface;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @NamePrefix("orob2b_api_tax_")
 */
class TaxController extends RestController implements ClassResourceInterface
{
    /**
     * @ApiDoc(
     *      description="Delete tax",
     *      resource=true
     * )
     * @Acl(
     *      id="orob2b_tax_delete",
     *      type="entity",
     *      class="OroB2BTaxBundle:Tax",
     *      permission="DELETE"
     * )
     *
     * @param int $id
     * @return Response
     */
    public function deleteAction($id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('orob2b_tax.manager.tax.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        throw new \BadMethodCallException('Not implemented');
    }
}

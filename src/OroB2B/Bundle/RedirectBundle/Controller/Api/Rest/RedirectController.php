<?php

namespace OroB2B\Bundle\RedirectBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\NamePrefix;

use Symfony\Component\HttpFoundation\Response;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @RouteResource("slug")
 * @NamePrefix("orob2b_api_")
 */
class RedirectController extends RestController implements ClassResourceInterface
{
    /**
     * Get slug for string
     *
     * @Get("/redirect/slugify/{string}", requirements={
     *     "string": ".+"
     * }))
     *
     * @ApiDoc(
     *      description="Get slug for string",
     *      resource=true
     * )
     *
     * @Acl(
     *      id="orob2b_redirect_view",
     *      type="entity",
     *      class="OroB2BRedirectBundle:Slug",
     *      permission="VIEW"
     * )
     *
     * @param string $string
     * @return Response
     */
    public function slugifyAction($string)
    {
        $slug = ['slug' => $this->get('orob2b_redirect.slug.generator')->slugify($string)];
        return new Response(json_encode($slug), Codes::HTTP_OK);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('orob2b_redirect.slug.manager.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        throw new \LogicException('This method should not be called');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        throw new \LogicException('This method should not be called');
    }
}

<?php

namespace OroB2B\Bundle\RedirectBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\NamePrefix;

use Symfony\Component\HttpFoundation\Response;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

/**
 * @RouteResource("slug")
 * @NamePrefix("orob2b_api_")
 */
class RedirectController extends FOSRestController
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
        return new Response(json_encode($slug), Response::HTTP_OK);
    }
}

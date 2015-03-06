<?php

namespace OroB2B\Bundle\CatalogBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\NamePrefix;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;

/**
 * @NamePrefix("orob2b_api_")
 */
class CategoryController extends RestController implements ClassResourceInterface
{
    /**
     * @ApiDoc(
     *      description="Delete catalog category",
     *      resource=true
     * )
     * @Acl(
     *      id="orob2b_catalog_category_delete",
     *      type="entity",
     *      class="OroB2BCatalogBundle:Category",
     *      permission="DELETE"
     * )
     *
     * @param int $id
     * @return Response
     */
    public function deleteAction($id)
    {
        $manager = $this->getManager();
        /** @var CategoryRepository $repository */
        $repository = $manager->getRepository('OroB2BCatalogBundle:Category');

        /** @var Category $category */
        $category = $manager->find($id);
        if (!$category->getParentCategory() && $category == $repository->getMasterCatalogRoot()) {
            throw new \LogicException('Master catalog root can not be removed');
        }

        return $this->handleDeleteRequest($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('orob2b_catalog.category.manager.api');
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

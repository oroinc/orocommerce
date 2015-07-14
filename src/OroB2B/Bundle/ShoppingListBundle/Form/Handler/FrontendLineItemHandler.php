<?php
namespace OroB2B\Bundle\ShoppingListBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;

class FrontendLineItemHandler
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param Request        $request
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(Request $request, DoctrineHelper $doctrineHelper)
    {
        $this->request = $request;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param FormInterface $form
     * @param LineItem      $lineItem
     *
     * @return bool
     */
    public function handle(FormInterface $form, LineItem $lineItem)
    {
        $form->setData($lineItem);
        $form->submit($this->request);

        if (!$form->isValid()) {
            return false;
        }

        $manager = $this->doctrineHelper->getEntityManager($lineItem);
        $manager->persist($lineItem);
        $manager->flush();

        return true;
    }
}

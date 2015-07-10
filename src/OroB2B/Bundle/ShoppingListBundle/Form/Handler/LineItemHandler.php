<?php
namespace OroB2B\Bundle\ShoppingListBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;

class LineItemHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var ObjectManager */
    protected $om;

    /** @var ObjectManager */
    protected $savedId;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param ObjectManager $om
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $om
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->om = $om;
    }

    /**
     * @param LineItem $lineItem
     *
     * @return bool
     */
    public function process(LineItem $lineItem)
    {
        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $this->form->submit($this->request);
            if ($this->form->isValid()) {
                $existingLineItem = $this->getExistingLineItem($lineItem);
                if ($existingLineItem) {
                    $existingLineItem->setQuantity($lineItem->getQuantity() + $existingLineItem->getQuantity());
                    $this->savedId = $existingLineItem->getId();
                } else {
                    $this->om->persist($lineItem);
                    $this->savedId = $lineItem->getId();
                }

                $this->om->flush();
                return true;
            }
        }

        return false;
    }

    /**
     * Update savedId for widget result
     *
     * @param array $result
     *
     * @return array
     */
    public function updateSavedId(array $result)
    {
        $result['savedId'] = $this->savedId;

        return $result;
    }

    /**
     * @param LineItem $lineItem
     *
     * @return LineItem|null
     */
    protected function getExistingLineItem(LineItem $lineItem)
    {
        return $this->om
            ->getRepository('OroB2B\Bundle\ShoppingListBundle\Entity\LineItem')
            ->findByProductAndUnit($lineItem->getShoppingList(), $lineItem->getProduct(), $lineItem->getUnit());
    }
}
<?php

namespace Oro\Bundle\PromotionBundle\EventListener\ORM;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Manager\AppliedDiscountManager;

class OrderEventListener
{
    /**@var AppliedDiscountManager */
    protected $discountManager;

    /** @param AppliedDiscountManager $appliedDiscountManager */
    public function __construct(AppliedDiscountManager $appliedDiscountManager)
    {
        $this->discountManager = $appliedDiscountManager;
    }

    /**
     * @param Order $order
     * @param LifecycleEventArgs $args
     */
    public function prePersist(Order $order, LifecycleEventArgs $args)
    {
        /** @var $appliedDiscounts */
        $appliedDiscounts = $this->discountManager->getAppliedDiscounts($order);
        if ($appliedDiscounts) {
            $entityManager = $args->getEntityManager();
            foreach ($appliedDiscounts as $discount) {
                $entityManager->persist($discount);
            }
        }
    }
}

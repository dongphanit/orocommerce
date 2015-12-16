<?php

namespace OroB2B\Bundle\AccountBundle\Model\Action;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface;

class ResolveProductVisibility extends AbstractAction
{
    /**
     * @var CacheBuilderInterface
     */
    protected $cacheBuilder;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * Empty constructor, no extra dependencies
     */
    public function __construct()
    {
    }

    /**
     * @param ManagerRegistry $registry
     */
    public function setRegistry(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param CacheBuilderInterface $cacheBuilder
     */
    public function setCacheBuilder(CacheBuilderInterface $cacheBuilder)
    {
        $this->cacheBuilder = $cacheBuilder;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        if (!$context instanceof ProcessData) {
            throw new \LogicException('This action can be called only from process context');
        }

        $visibilityEntity = $context->getEntity();
        if (!$visibilityEntity instanceof VisibilityInterface) {
            throw new \LogicException('Resolvable entity must implement VisibilityInterface');
        }

        $this->getEntityManager()->transactional(
            function () use ($visibilityEntity) {
                $this->cacheBuilder->resolveVisibilitySettings($visibilityEntity);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
    }

    /**
     * All resolved product visibility entities should be stored together, so entity manager should be the same too
     *
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->registry->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved');
    }
}

<?php

namespace Oro\Bundle\ActionBundle\Model;

use Oro\Bundle\WorkflowBundle\Model\Condition\AbstractCondition;
use Oro\Bundle\WorkflowBundle\Model\Condition\Configurable as ConfigurableCondition;

use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class Action
{
    /** @var ConditionFactory */
    protected $conditionFactory;

    /** @var ActionDefinition */
    protected $definition;

    /** @var AbstractCondition[] */
    protected $preConditions;

    /** @var AbstractCondition[] */
    protected $conditions;

    /** @var bool */
    private $enabled;

    /** @var string */
    private $name;

    /**
     * @param ConditionFactory $conditionFactory
     * @param ActionDefinition $definition
     */
    public function __construct(ConditionFactory $conditionFactory, ActionDefinition $definition)
    {
        $this->conditionFactory = $conditionFactory;
        $this->definition = $definition;
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param ActionDefinition $definition
     * @return $this
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;

        return $this;
    }

    /**
     * @return ActionDefinition
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @return AbstractCondition[]
     */
    protected function getPreConditions()
    {
        if ($this->preConditions === null) {
            $this->preConditions = [];
            $preConditionsConfig = $this->definition->getPreConditionsConfiguration();
            if ($preConditionsConfig) {
                foreach ($preConditionsConfig as $conditionConfig) {
                    $this->preConditions[] = $this->conditionFactory
                        ->create(ConfigurableCondition::ALIAS, $conditionConfig);
                }
            }
        }

        return $this->preConditions;
    }

    /**
     * @return AbstractCondition[]
     */
    protected function getConditions()
    {
        if ($this->conditions === null) {
            $this->conditions = [];
            $conditionsConfig = $this->definition->getConditionsConfiguration();
            if ($conditionsConfig) {
                foreach ($conditionsConfig as $conditionConfig) {
                    $this->conditions[] = $this->conditionFactory
                        ->create(ConfigurableCondition::ALIAS, $conditionConfig);
                }
            }
        }

        return $this->conditions;
    }

    /**
     * @param ActionContext $context
     */
    public function init(ActionContext $context)
    {
        // ToDo - implement init
    }

    /**
     * @param ActionContext $context
     */
    public function execute(ActionContext $context)
    {
        // ToDo - implement execution
    }

    /**
     * @param ActionContext $context
     * @return bool
     */
    public function isAllowed(ActionContext $context)
    {
        foreach ($this->getPreConditions() as $condition) {
            if (!$condition->evaluate($context->getEntity())) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param ActionContext $context
     * @return bool
     */
    public function isApplicable(ActionContext $context)
    {
        foreach ($this->getConditions() as $condition) {
            if (!$condition->evaluate($context->getEntity())) {
                return false;
            }
        }

        return true;
    }
}

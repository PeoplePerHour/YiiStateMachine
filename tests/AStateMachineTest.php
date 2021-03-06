<?php
Yii::import("packages.stateMachine.*");
/**
 * Tests for the {@AStateMachine} class
 * @author Charles Pick
 * @package packages.stateMachine.tests
 */
class AStateMachineTest extends CTestCase {
	/**
	 * Tests the state machine magic methods
	 */
	public function testMagicMethods() {
		$machine = new AStateMachine();
		$machine->setStates(array(
							new ExampleEnabledState("enabled",$machine),
							new ExampleDisabledState("disabled",$machine),
							));
		$machine->defaultStateName = "enabled";
		$this->assertTrue($machine->is("enabled"));
		$this->assertFalse($machine->is("disabled"));
		$this->assertTrue($machine->isEnabled);
		$this->assertTrue(isset($machine->testProperty));
		$machine->disable();
		$this->assertTrue($machine->is("disabled"));
		$this->assertFalse($machine->is("enabled"));
		$this->assertFalse($machine->isEnabled);
		$this->assertFalse(isset($machine->testProperty));
	}

	/**
	 * Tests adding and removing states from a state machine
	 */
	public function testAddRemoveStates() {
		$machine = new AStateMachine();

		$machine->addState(new ExampleEnabledState("enabled",$machine));
		$this->assertFalse(isset($machine->testProperty));
		$machine->defaultStateName = "enabled";
		$this->assertTrue(isset($machine->testProperty));
		$machine->removeState("enabled");
		$this->assertFalse(isset($machine->testProperty));
		$this->assertNull($machine->getState());
	}

	/**
	 * Tests the transition events
	 */
	public function testTransitions() {
		$machine = new AStateMachine();
		$machine->setStates(array(
							new ExampleEnabledState("enabled",$machine),
							new ExampleDisabledState("disabled",$machine),
							new ExampleIntermediateState("intermediate", $machine),
							));
		$machine->defaultStateName = "enabled";
		$machine->enableTransitionHistory = true;
		$machine->maximumTransitionHistorySize = 2;
		$this->assertFalse($machine->transition("intermediate")); // intermediate state blocks transition from enabled -> intermediate
		$this->assertTrue($machine->transition("disabled"));
		$this->assertEquals(2, $machine->getTransitionHistory()->count());
		$this->assertTrue($machine->transition("intermediate")); // should work
		$this->assertEquals(2, $machine->getTransitionHistory()->count());
	}
	/**
	 * Tests for the behavior functionality
	 */
	public function testBehavior() {
		$machine = new AStateMachine();
		$machine->setStates(array(
							new ExampleEnabledState("enabled",$machine),
							new ExampleDisabledState("disabled",$machine),
							));
		$machine->defaultStateName = "enabled";

		$component = new CComponent();
		$component->attachBehavior("status",$machine);
		$this->assertTrue($component->is("enabled"));
		$this->assertTrue($component->demoMethod());
		$this->assertTrue($component->transition("disabled"));
		$this->assertTrue($component->status->is("disabled"));
	}

    public function testEvents()
    {
        $machine = $this->getMock("AStateMachine", array("onBeforeTransition", "onAfterTransition"));

        $enabled  = $this->getMock("AState", array("onBeforeEnter", "onBeforeExit", "onAfterEnter", "onAfterExit"), array("enabled", $machine));
        $disabled = $this->getMock("AState", array("onBeforeEnter", "onBeforeExit", "onAfterEnter", "onAfterExit"), array("disabled", $machine));

        $machine->setStates(array($enabled,$disabled));
		$machine->defaultStateName = "enabled";
        
        $params           = array("param"=>1, "param"=>2);
        $transition       = new AStateTransition($machine, $params);
        $transition->to   = $disabled;
        $transition->from = $enabled;

        $machine->expects($this->once())
            ->method("onBeforeTransition")
            ->with($transition);

        $machine->expects($this->once())
            ->method("onAfterTransition")
            ->with($transition);

        $enabledTransition = new AStateTransition($enabled);
        $enabledTransition->to = $disabled;
        $enabledTransition->from = $enabled;

        $enabled->expects($this->never())
            ->method("onBeforeEnter");
        $enabled->expects($this->never())
            ->method("onAfterEnter");
        $enabled->expects($this->once())
            ->method("onBeforeExit")
            ->with($enabledTransition);
        $enabled->expects($this->once())
            ->method("onAfterExit")
            ->with($enabledTransition);
    
        $disabledTransition = new AStateTransition($disabled);
        $disabledTransition->to = $disabled;
        $disabledTransition->from = $enabled;

        $disabled->expects($this->never())
            ->method("onBeforeExit");
        $disabled->expects($this->never())
             ->method("onAfterExit");
        $disabled->expects($this->once())
             ->method("onBeforeEnter")
             ->with($disabledTransition);
        $disabled->expects($this->once())
            ->method("onAfterEnter")
            ->with($disabledTransition);

        $this->assertTrue($machine->transition("disabled", $params));
    }
}


/**
 * An example of an enabled state
 * @author Charles Pick
 * @package packages.stateMachine.tests
 */
class ExampleEnabledState extends AState {
	/**
	 * An example of a state property
	 * @var boolean
	 */
	public $isEnabled = true;

	/**
	 * An example of a state property
	 * @var boolean
	 */
	public $testProperty = true;

	/**
	 * Sets the state to disabled
	 */
	public function disable() {
		$this->_machine->transition("disabled");
	}

	public function demoMethod() {
		return true;
	}
}
/**
 * An example of a disabled state
 * @author Charles Pick
 * @package packages.stateMachine.tests
 */
class ExampleDisabledState extends AState {
	/**
	 * An example of a state property
	 * @var boolean
	 */
	public $isEnabled = false;
	/**
	 * Sets the state to enabled
	 */
	public function enable() {
		$this->_machine->transition("enabled");
	}
}

/**
 * An example of an intermediate state
 * @author Charles Pick
 * @package packages.stateMachine.tests
 */
class ExampleIntermediateState extends AState {
	/**
	 * An example of a state property
	 * @var boolean
	 */
	public $isEnabled = null;

	/**
	 * Blocks the transition from enabled to intermediate
	 * @param AState $fromState the state we're transitioning from
	 * @return boolean whether the transition should continue
	 */
	public function beforeEnter() {
		$fromState = $this->_machine->getState();
		if ($fromState->getName() == "enabled") {
			return false;
		}
		return parent::beforeEnter();
	}
}

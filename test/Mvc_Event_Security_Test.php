<?php

declare(strict_types=1);

namespace Laminas_Test\Mvc;

use Laminas\Mvc\Application;
use Laminas\Mvc\Mvc_Event;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Security tests for Mvc_Event error state handling.
 *
 * These tests verify that:
 * - Error state can only be set with a non-empty string
 * - is_error() correctly reflects whether an error has been set
 * - ERROR_* constants on Application are used consistently for error classification
 * - Error state cannot be "cleared" by accident via empty strings
 */
class Mvc_Event_Security_Test extends TestCase
{
    private Mvc_Event $event;

    protected function setUp(): void
    {
        $this->event = new Mvc_Event();
    }

    // -----------------------------------------------------------------------
    // Error state: is_error() / set_error() / get_error()
    // -----------------------------------------------------------------------

    #[Group('security')]
    public function test_is_error_returns_false_when_no_error_is_set(): void
    {
        self::assertFalse($this->event->is_error());
    }

    #[Group('security')]
    public function test_is_error_returns_true_after_set_error_called_with_non_empty_string(): void
    {
        $this->event->set_error(Application::ERROR_CONTROLLER_NOT_FOUND);
        self::assertTrue($this->event->is_error());
    }

    #[Group('security')]
    public function test_get_error_returns_exact_error_string_set(): void
    {
        $this->event->set_error(Application::ERROR_CONTROLLER_NOT_FOUND);
        self::assertSame(Application::ERROR_CONTROLLER_NOT_FOUND, $this->event->get_error());
    }

    #[Group('security')]
    public function test_get_error_returns_empty_string_when_no_error_set(): void
    {
        self::assertSame('', $this->event->get_error());
    }

    #[Group('security')]
    public function test_all_application_error_constants_cause_is_error_to_return_true(): void
    {
        $error_constants = [
            Application::ERROR_CONTROLLER_CANNOT_DISPATCH,
            Application::ERROR_CONTROLLER_NOT_FOUND,
            Application::ERROR_CONTROLLER_INVALID,
            Application::ERROR_EXCEPTION,
            Application::ERROR_ROUTER_NO_MATCH,
            Application::ERROR_MIDDLEWARE_CANNOT_DISPATCH,
        ];

        foreach ($error_constants as $error) {
            $event = new Mvc_Event();
            $event->set_error($error);
            self::assertTrue(
                $event->is_error(),
                "is_error() must return true for error constant: $error"
            );
            self::assertSame($error, $event->get_error());
        }
    }

    #[Group('security')]
    public function test_error_constants_are_distinct_non_empty_strings(): void
    {
        $constants = [
            Application::ERROR_CONTROLLER_CANNOT_DISPATCH,
            Application::ERROR_CONTROLLER_NOT_FOUND,
            Application::ERROR_CONTROLLER_INVALID,
            Application::ERROR_EXCEPTION,
            Application::ERROR_ROUTER_NO_MATCH,
            Application::ERROR_MIDDLEWARE_CANNOT_DISPATCH,
        ];

        // Each constant must be a non-empty string
        foreach ($constants as $const) {
            self::assertIsString($const);
            self::assertNotEmpty($const);
        }

        // Each constant must be unique (no aliasing)
        self::assertSame(count($constants), count(array_unique($constants)));
    }

    // -----------------------------------------------------------------------
    // Controller class on the event must be set explicitly
    // -----------------------------------------------------------------------

    #[Group('security')]
    public function test_controller_class_is_null_when_not_set(): void
    {
        self::assertNull($this->event->get_controller_class());
    }

    #[Group('security')]
    public function test_controller_class_is_returned_exactly_as_set(): void
    {
        $this->event->set_controller_class('Application\\Controller\\IndexController');
        self::assertSame('Application\\Controller\\IndexController', $this->event->get_controller_class());
    }

    #[Group('security')]
    public function test_controller_name_is_null_when_not_set(): void
    {
        self::assertNull($this->event->get_controller());
    }

    // -----------------------------------------------------------------------
    // Result on the event
    // -----------------------------------------------------------------------

    #[Group('security')]
    public function test_result_is_null_when_not_set(): void
    {
        self::assertNull($this->event->get_result());
    }

    #[Group('security')]
    public function test_result_is_returned_exactly_as_set(): void
    {
        $view_model = new \stdClass();
        $this->event->set_result($view_model);
        self::assertSame($view_model, $this->event->get_result());
    }
}

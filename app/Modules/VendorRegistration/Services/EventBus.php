<?php
namespace VMP\Modules\VendorRegistration\Services;

class EventBus
{
    /**
     * Dispatch an event object. Uses WP actions under the hood.
     * Event name: vmp_event_{short_class_name}
     */
    public function dispatch(object $event): void
    {
        $class = (new \ReflectionClass($event))->getShortName();
        $action = 'vmp_event_' . strtolower($class);
        try {
            do_action($action, $event);
        } catch (\Throwable $e) {
            error_log(sprintf('EventBus dispatch error for %s: %s', $action, $e->getMessage()));
        }
    }
}

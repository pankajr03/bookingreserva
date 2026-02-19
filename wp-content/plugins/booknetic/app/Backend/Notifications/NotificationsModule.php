<?php

namespace BookneticApp\Backend\Notifications;

use BookneticApp\Backend\Notifications\Controllers\NotificationController;
use BookneticApp\Backend\Notifications\Registerer\NotificationWorkflowEventRegisterer;
use BookneticApp\Backend\Notifications\Registerer\NotificationWorkflowEvents\AppointmentPaidNotificationWorkflowEvent;
use BookneticApp\Backend\Notifications\Registerer\NotificationWorkflowEvents\BookingEndsNotificationWorkflowEvent;
use BookneticApp\Backend\Notifications\Registerer\NotificationWorkflowEvents\BookingRescheduleNotificationWorkflowEvent;
use BookneticApp\Backend\Notifications\Registerer\NotificationWorkflowEvents\BookingStartsNotificationWorkflowEvent;
use BookneticApp\Backend\Notifications\Registerer\NotificationWorkflowEvents\BookingStatusChangedNotificationWorkflowEvent;
use BookneticApp\Backend\Notifications\Registerer\NotificationWorkflowEvents\CustomerBirthdayNotificationWorkflowEvent;
use BookneticApp\Backend\Notifications\Registerer\NotificationWorkflowEvents\CustomerForgetPasswordNotificationWorkflowEvent;
use BookneticApp\Backend\Notifications\Registerer\NotificationWorkflowEvents\CustomerResetPasswordNotificationWorkflowEvent;
use BookneticApp\Backend\Notifications\Registerer\NotificationWorkflowEvents\CustomerSignupNotificationWorkflowEvent;
use BookneticApp\Backend\Notifications\Registerer\NotificationWorkflowEvents\NewBookingNotificationWorkflowEvent;
use BookneticApp\Backend\Notifications\Registerer\NotificationWorkflowEvents\NewWpUserCustomerCreatedNotificationWorkflowEvent;
use BookneticApp\Backend\Notifications\Repositories\NotificationRepository;
use BookneticApp\Backend\Notifications\Services\NotificationService;
use BookneticApp\Providers\Core\RestGroup;
use BookneticApp\Providers\IoC\Container;
use ReflectionException;

class NotificationsModule
{
    /**
     * @throws ReflectionException
     */
    public static function registerRestRoutes(): void
    {
        $router = new RestGroup('notifications');
        $controller = Container::get(NotificationController::class);

        $router->get('', [$controller, 'getAll']);
        $router->post('mark-as-read', [$controller, 'markAsRead']);
        $router->post('mark-all-as-read', [$controller, 'markAllAsRead']);
        $router->delete('clear', [$controller, 'clear']);
    }

    public static function registerDependencies(): void
    {
        Container::addBulk([
            NotificationController::class,
            NotificationService::class,
            NotificationRepository::class,
        ]);
    }

    public static function registerNotificationWorkflowEvents(): void
    {
        NotificationWorkflowEventRegisterer::registerEvents('booking_new', NewBookingNotificationWorkflowEvent::class);
        NotificationWorkflowEventRegisterer::registerEvents('booking_rescheduled', BookingRescheduleNotificationWorkflowEvent::class);
        NotificationWorkflowEventRegisterer::registerEvents('booking_status_changed', BookingStatusChangedNotificationWorkflowEvent::class);
        NotificationWorkflowEventRegisterer::registerEvents('customer_birthday', CustomerBirthdayNotificationWorkflowEvent::class);
        NotificationWorkflowEventRegisterer::registerEvents('booking_starts', BookingStartsNotificationWorkflowEvent::class);
        NotificationWorkflowEventRegisterer::registerEvents('booking_ends', BookingEndsNotificationWorkflowEvent::class);
        NotificationWorkflowEventRegisterer::registerEvents('new_wp_user_customer_created', NewWpUserCustomerCreatedNotificationWorkflowEvent::class);
        NotificationWorkflowEventRegisterer::registerEvents('customer_forgot_password', CustomerForgetPasswordNotificationWorkflowEvent::class);
        NotificationWorkflowEventRegisterer::registerEvents('customer_reset_password', CustomerResetPasswordNotificationWorkflowEvent::class);
        NotificationWorkflowEventRegisterer::registerEvents('appointment_paid', AppointmentPaidNotificationWorkflowEvent::class);
        NotificationWorkflowEventRegisterer::registerEvents('customer_signup', CustomerSignupNotificationWorkflowEvent::class);
    }
}

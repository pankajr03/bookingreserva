<?php

namespace BookneticSaaS;

use BookneticApp\Providers\Common\ShortCodeService;
use BookneticApp\Providers\Common\WorkflowDriversManager;
use BookneticApp\Providers\Common\WorkflowEventsManager;
use BookneticApp\Providers\Core\Capabilities;
use BookneticSaaS\Backend\Notifications\InAppNotification;
use BookneticSaaS\Backend\Notifications\NotificationWorkflowEvents\TenantDeleteNotificationWorkflowEvent;
use BookneticSaaS\Backend\Notifications\NotificationWorkflowEvents\TenantSubscribedNotificationWorkflowEvent;
use BookneticSaaS\Backend\Notifications\Registerer\NotificationWorkflowEventRegisterer;
use BookneticApp\Providers\Core\Notifications;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Core\Permission as PermissionRegular;
use BookneticApp\Providers\Core\Route;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\UI\Abstracts\AbstractMenuUI;
use BookneticApp\Providers\UI\MenuUI;
use BookneticSaaS\Backend\Billing\Ajax;
use BookneticSaaS\Backend\Billing\Controller;
use BookneticSaaS\Backend\Notifications\NotificationWorkflowEvents\TenantDepositedNotificationWorkflowEvent;
use BookneticSaaS\Backend\Notifications\NotificationWorkflowEvents\TenantForgetPasswordNotificationWorkflowEvent;
use BookneticSaaS\Backend\Notifications\NotificationWorkflowEvents\TenantNotifiedNotificationWorkflowEvent;
use BookneticSaaS\Backend\Notifications\NotificationWorkflowEvents\TenantPaidNotificationWorkflowEvent;
use BookneticSaaS\Backend\Notifications\NotificationWorkflowEvents\TenantResetPasswordNotificationWorkflowEvent;
use BookneticSaaS\Backend\Notifications\NotificationWorkflowEvents\TenantSignupComplatedNotificationWorkflowEvent;
use BookneticSaaS\Backend\Notifications\NotificationWorkflowEvents\TenantSignupNotificationWorkflowEvent;
use BookneticSaaS\Backend\Notifications\NotificationWorkflowEvents\TenantUnsubscribedNotificationWorkflowEvent;
use BookneticSaaS\Integrations\PaymentGateways\WooCoommerce;
use BookneticSaaS\Models\Plan;
use BookneticSaaS\Models\Tenant;
use BookneticSaaS\Models\TenantBilling;
use BookneticSaaS\Models\TenantFormInput;
use BookneticSaaS\Providers\Common\Divi\includes\BookneticSaaSDivi;
use BookneticSaaS\Providers\Common\Elementor\BookneticSaaSElementor;
use BookneticSaaS\Providers\Common\EmailWorkflowDriver;
use BookneticSaaS\Providers\Common\GoogleGmailService;
use BookneticSaaS\Providers\Common\ShortCodeServiceImpl;
use BookneticApp\Providers\Core\Backend as CoreBackend;
use BookneticSaaS\Providers\Core\Backend;
use BookneticSaaS\Providers\Core\Route as SaaSRoute;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Providers\Helpers\TenantHelper;
use BookneticSaaS\Providers\UI\MenuUI as SaaSMenuUI;

class Config
{
    private static $planCaches = [];

    /**
     * @var WorkflowDriversManager
     */
    private static $workflowDriversManager;

    /**
     * @var WorkflowEventsManager
     */
    private static $workflowEventsManager;

    /**
     * @var ShortCodeService
     */
    private static $shortCodeService;

    /**
     * @return array
     */
    public static function getPlanCaches()
    {
        return self::$planCaches;
    }

    /**
     * @return ShortCodeService
     */
    public static function getShortCodeService()
    {
        return self::$shortCodeService;
    }

    public static function init()
    {
        self::$shortCodeService = new ShortCodeService();
        self::$workflowDriversManager = new WorkflowDriversManager();
        self::$workflowEventsManager = new WorkflowEventsManager();
        self::$workflowEventsManager->setDriverManager(self::$workflowDriversManager);
        self::$workflowEventsManager->setShortcodeService(self::$shortCodeService);

        if (! class_exists(\BookneticApp\Providers\Helpers\Helper::class)) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';

            activate_plugins('booknetic/init.php');
        }

        self::registerWPUserRoles();
        self::registerSigningPage();
        self::registerCoreCapabilites();
        self::registerCoreShortCodes();
        self::registerCoreWorkflowEvents();
        self::registerCoreWorkflowDrivers();
        self::registerCronActions();

        add_action('bkntc_backend', [ self::class, 'registerTenantActivities' ]);
        add_filter('bkntc_tenant_capability_filter', [ self::class, 'tenantCapabilities' ], 10, 2);
        add_filter('bkntc_capability_limit_filter', [ self::class, 'tenantLimits' ], 10, 2);

        add_action('bkntcsaas_backend', [ self::class, 'registerCoreRoutes' ]);
        add_action('bkntcsaas_backend', [ self::class, 'registerCoreMenus' ]);

        add_filter('woocommerce_prevent_admin_access', function () {
            return false;
        });

        add_action('wp_loaded', [ WooCoommerce::class, 'initFilters' ]);
        add_action('elementor/widgets/register', [ BookneticSaaSElementor::class, 'registerWidgets' ]);
        add_filter('bkntcsaas_booking_page_redirect', function ($postID) {
            try {
                return ! (class_exists("\Elementor\Plugin") && \Elementor\Plugin::$instance->db->is_built_with_elementor($postID));
            } catch (\Exception $e) {
                return $postID;
            }
        });

        if (class_exists('DiviExtension')) {
            new BookneticSaaSDivi();
        }

        add_action('template_include', function ($template) {
            if (isset($_GET[ 'bkntc_saas_preview' ]) && $_SERVER[ 'REQUEST_METHOD' ] === 'POST') {
                $shortcode = Helper::_post('shortcode', '', 'str');
                echo do_shortcode($shortcode);
                print_late_styles();
                print_footer_scripts();
                exit;
            }

            return $template;
        });

        self::checkGmailSMTPCallback();
        self::redirectTenantToBooknetic();
    }

    public static function redirectTenantToBooknetic()
    {
        if (!Permission::isTenant() || Helper::getOption('disallow_tenants_to_enter_wp_dashboard') !== 'on') {
            return;
        }

        $allowed_page = CoreBackend::getSlugName();

        add_action('admin_page_access_denied', function () use ($allowed_page) {
            wp_redirect(admin_url('admin.php?page=' . $allowed_page));
            exit;
        });

        add_action('admin_init', function () use ($allowed_page) {
            global $pagenow;
            $exceptions = ['admin-ajax.php', 'async-upload.php'];
            $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

            if (
                !in_array($pagenow, $exceptions) &&
                !($pagenow === 'admin.php' && $current_page === $allowed_page)
            ) {
                wp_redirect(admin_url('admin.php?page=' . $allowed_page));
                exit;
            }
        });
    }

    public static function registerWPUserRoles()
    {
        add_role('booknetic_saas_tenant', bkntcsaas__('Booknetic SaaS Tenant'), [
            'read' => true,
            'edit_posts' => false,
            'upload_files' => true
        ]);
    }

    public static function registerSigningPage()
    {
        $sign_in_page = Helper::getOption('sign_in_page');

        if (! empty($sign_in_page) && ($sign_in_page_link = get_permalink($sign_in_page)) && ! empty($sign_in_page_link)) {
            add_filter('login_url', function ($login_url, $redirect) use ($sign_in_page_link) {
                if (! empty($redirect)) {
                    $sign_in_page_link = add_query_arg('redirect_to', urlencode($redirect), $sign_in_page_link);
                }

                return $sign_in_page_link;
            }, 10, 2);
        }
    }

    public static function registerCoreCapabilites()
    {
    }

    public static function registerCoreShortCodes()
    {
        $shortCodeService = self::$shortCodeService;
        $shortCodeService->addReplacer([ ShortCodeServiceImpl::class, 'replace' ]);

        $shortCodeService->registerCategory('tenant_info', bkntcsaas__('Tenant Info'));
        $shortCodeService->registerCategory('plan_and_billing_info', bkntcsaas__('Plan & Billing'));
        $shortCodeService->registerCategory('customer_info', bkntcsaas__('Customer info'));

        $shortCodeService->registerShortCode('tenant_id', [
            'name' => bkntcsaas__('Tenant ID'),
            'category' => 'tenant_info',
            'depends' => 'tenant_id',
        ]);

        $shortCodeService->registerShortCode('tenant_full_name', [
            'name' => bkntcsaas__('Tenant Full Name'),
            'category' => 'tenant_info',
            'depends' => 'tenant_id',
        ]);

        $shortCodeService->registerShortCode('tenant_email', [
            'name' => bkntcsaas__('Tenant Email'),
            'category' => 'tenant_info',
            'depends' => 'tenant_id',
            'kind' => 'email'
        ]);

        $shortCodeService->registerShortCode('tenant_registration_date', [
            'name' => bkntcsaas__('Tenant Registration Date'),
            'category' => 'tenant_info',
            'depends' => 'tenant_id',
        ]);

        $shortCodeService->registerShortCode('plan_id', [
            'name' => bkntcsaas__('Plan ID'),
            'category' => 'plan_and_billing_info',
            'depends' => 'tenant_id',
        ]);

        $shortCodeService->registerShortCode('plan_name', [
            'name' => bkntcsaas__('Plan Name'),
            'category' => 'plan_and_billing_info',
            'depends' => 'tenant_id',
        ]);

        $shortCodeService->registerShortCode('plan_color', [
            'name' => bkntcsaas__('Plan Color'),
            'category' => 'plan_and_billing_info',
            'depends' => 'tenant_id',
        ]);

        $shortCodeService->registerShortCode('plan_description', [
            'name' => bkntcsaas__('Plan Description'),
            'category' => 'plan_and_billing_info',
            'depends' => 'tenant_id',
        ]);

        $shortCodeService->registerShortCode('deposit_amount', [
            'name' => bkntcsaas__('Deposit Amount'),
            'category' => 'plan_and_billing_info',
            'depends' => 'deposit_amount',
        ]);

        $shortCodeService->registerShortCode('payment_amount', [
            'name' => bkntcsaas__('Payment Amount'),
            'category' => 'plan_and_billing_info',
            'depends' => 'tenant_id',
        ]);

        $shortCodeService->registerShortCode('payment_method', [
            'name' => bkntcsaas__('Payment Method'),
            'category' => 'plan_and_billing_info',
            'depends' => 'tenant_id',
        ]);

        $shortCodeService->registerShortCode('payment_cycle', [
            'name' => bkntcsaas__('Payment Cycle'),
            'category' => 'plan_and_billing_info',
            'depends' => 'tenant_id',
        ]);

        // GENERALS
        $shortCodeService->registerShortCode('company_name', [
            'name' => bkntcsaas__('Tenant Company Name'),
            'category' => 'tenant_info',
            'depends' => 'tenant_id',
        ]);

        $shortCodeService->registerShortCode('company_address', [
            'name' => bkntcsaas__('Tenant Company Address'),
            'category' => 'tenant_info',
            'depends' => 'tenant_id',
        ]);

        $shortCodeService->registerShortCode('company_phone_number', [
            'name' => bkntcsaas__('Tenant Company Phone'),
            'category' => 'tenant_info',
            'depends' => 'tenant_id',
            'kind' => 'phone',
        ]);

        $shortCodeService->registerShortCode('company_website', [
            'name' => bkntcsaas__('Tenant Company Website'),
            'category' => 'tenant_info',
            'depends' => 'tenant_id',
        ]);

        $shortCodeService->registerShortCode('company_image_url', [
            'name' => bkntcsaas__('Tenant Company Image URL'),
            'category' => 'tenant_info',
            'depends' => 'tenant_id',
        ]);

        $shortCodeService->registerShortCode('tenant_domain', [
            'name' => bkntcsaas__('Tenant Domain'),
            'category' => 'tenant_info',
            'depends' => 'tenant_id',
        ]);

        $shortCodeService->registerShortCode('url_to_complete_signup', [
            'name' => bkntcsaas__('URL To Complete Signup'),
            'category' => 'tenant_info',
            'depends' => 'tenant_id',
        ]);

        $shortCodeService->registerShortCode('subscription_expires_in', [
            'name' => bkntcsaas__('Tenant subscription expiration date'),
            'category' => 'tenant_info',
            'depends' => 'tenant_id',
        ]);

        $shortCodeService->registerShortCode('customer_full_name', [
            'name' => bkntcsaas__('Customer full name'),
            'category' => 'other',
            'depends' => 'customer_id'
        ]);
        $shortCodeService->registerShortCode('customer_first_name', [
            'name' => bkntcsaas__('Customer first name'),
            'category' => 'other',
            'depends' => 'customer_id'
        ]);
        $shortCodeService->registerShortCode('customer_last_name', [
            'name' => bkntcsaas__('Customer last name'),
            'category' => 'customer_info',
            'depends' => 'customer_id'
        ]);
        $shortCodeService->registerShortCode('customer_phone', [
            'name' => bkntcsaas__('Customer phone number'),
            'category' => 'customer_info',
            'depends' => 'customer_id',
            'kind' => 'phone'
        ]);
        $shortCodeService->registerShortCode('customer_email', [
            'name' => bkntcsaas__('Customer email'),
            'category' => 'customer_info',
            'depends' => 'customer_id',
            'kind' => 'email'
        ]);
        $shortCodeService->registerShortCode('customer_birthday', [
            'name' => bkntcsaas__('Customer birthdate'),
            'category' => 'customer_info',
            'depends' => 'customer_id'
        ]);
        $shortCodeService->registerShortCode('url_to_complete_customer_signup', [
            'name' => bkntcsaas__('URL to complete customer sign up'),
            'category' => 'others',
            'depends' => 'customer_id',
        ]);
        $shortCodeService->registerShortCode('url_to_reset_password', [
            'name' => bkntcsaas__('URL to reset customer password'),
            'category' => 'others',
            'depends' => 'customer_id'
        ]);
        $shortCodeService->registerShortCode('customer_notes', [
            'name' => bkntcsaas__('Customer notes'),
            'category' => 'customer_info',
            'depends' => 'customer_id'
        ]);
        $shortCodeService->registerShortCode('customer_profile_image_url', [
            'name' => bkntcsaas__('Customer image URL'),
            'category' => 'customer_info',
            'depends' => 'customer_id'
        ]);

        $shortCodeService->registerShortCode('sign_in_page', [
            'name' => bkntcsaas__('Booknetic Sign In Page'),
            'category' => 'others'
        ]);
        $shortCodeService->registerShortCode('sign_up_page', [
            'name' => bkntcsaas__('Booknetic Sign Up Page'),
            'category' => 'others'
        ]);

        $shortCodeService->registerShortCode('url_to_reset_password', [
            'name' => bkntcsaas__('URL To Reset Password'),
            'category' => 'tenant_info',
            'depends' => 'tenant_id',
        ]);

        foreach (TenantFormInput::fetchAll() as $tenantFormInput) {
            if (in_array($tenantFormInput[ 'type' ], [ 'label', 'link' ])) {
                continue;
            }

            if ($tenantFormInput->type === 'file') {
                $shortCodeService->registerShortCode('tenant_custom_field_' . $tenantFormInput[ 'id' ] . '_url', [
                    'name' => bkntcsaas__('Custom Field - ' . $tenantFormInput[ 'label' ] . ' [URL]'),
                    'category' => 'others',
                    'depends' => 'tenant_id',
                    'kind' => 'url'
                ]);

                $shortCodeService->registerShortCode('tenant_custom_field_' . $tenantFormInput[ 'id' ] . '_path', [
                    'name' => bkntcsaas__('Custom Field - ' . $tenantFormInput[ 'label' ] . ' [PATH]'),
                    'category' => 'others',
                    'depends' => 'tenant_id',
                    'kind' => 'file'
                ]);

                $shortCodeService->registerShortCode('tenant_custom_field_' . $tenantFormInput[ 'id' ] . '_name', [
                    'name' => bkntcsaas__('Custom Field - ' . $tenantFormInput[ 'label' ] . ' [NAME]'),
                    'category' => 'others',
                    'depends' => 'tenant_id',
                ]);
                continue;
            }

            $shortCodeService->registerShortCode('tenant_custom_field_' . $tenantFormInput[ 'id' ], [
                'name' => bkntcsaas__('Custom Field - ' . $tenantFormInput[ 'label' ]),
                'category' => 'others',
                'depends' => 'tenant_id',
            ]);
        }
    }

    public static function registerCoreWorkflowEvents()
    {
        self::$workflowEventsManager->get('tenant_signup')
            ->setTitle(bkntcsaas__('New tenant signed up'))
            ->setAvailableParams([ 'tenant_id' ]);
        NotificationWorkflowEventRegisterer::registerEvents('tenant_signup', TenantSignupNotificationWorkflowEvent::class);

        self::$workflowEventsManager->get('tenant_signup_completed')
            ->setTitle(bkntcsaas__('Tenant sign-up completed'))
            ->setAvailableParams([ 'tenant_id' ]);
        NotificationWorkflowEventRegisterer::registerEvents('tenant_signup_completed', TenantSignupComplatedNotificationWorkflowEvent::class);

        self::$workflowEventsManager->get('tenant_forgot_password')
            ->setTitle(bkntcsaas__('Tenant forgot password'))
            ->setAvailableParams([ 'tenant_id' ]);
        NotificationWorkflowEventRegisterer::registerEvents('tenant_forgot_password', TenantForgetPasswordNotificationWorkflowEvent::class);

        self::$workflowEventsManager->get('tenant_reset_password')
            ->setTitle(bkntcsaas__('Tenant password was reset'))
            ->setAvailableParams([ 'tenant_id' ]);
        NotificationWorkflowEventRegisterer::registerEvents('tenant_reset_password', TenantResetPasswordNotificationWorkflowEvent::class);

        self::$workflowEventsManager->get('tenant_subscribed')
            ->setTitle(bkntcsaas__('Tenant subscribed to a plan'))
            ->setAvailableParams([ 'tenant_id' ]);
        NotificationWorkflowEventRegisterer::registerEvents('tenant_subscribed', TenantSubscribedNotificationWorkflowEvent::class);

        self::$workflowEventsManager->get('tenant_deleted')
            ->setTitle(bkntcsaas__('Tenant deleted'))
            ->setAvailableParams([ 'tenant_id' ]);
        NotificationWorkflowEventRegisterer::registerEvents('tenant_deleted', TenantDeleteNotificationWorkflowEvent::class);

        self::$workflowEventsManager->get('tenant_unsubscribed')
            ->setTitle(bkntcsaas__('Tenant unsubscribed to a plan'))
            ->setAvailableParams([ 'tenant_id' ]);
        NotificationWorkflowEventRegisterer::registerEvents('tenant_unsubscribed', TenantUnsubscribedNotificationWorkflowEvent::class);

        self::$workflowEventsManager->get('tenant_paid')
            ->setTitle(bkntcsaas__('Tenant payment received'))
            ->setAvailableParams([ 'tenant_id' ]);
        NotificationWorkflowEventRegisterer::registerEvents('tenant_paid', TenantPaidNotificationWorkflowEvent::class);

        self::$workflowEventsManager->get('tenant_deposited')
            ->setTitle(bkntcsaas__('Tenant deposited'))
            ->setAvailableParams([ 'tenant_id', 'deposit_amount' ]);
        NotificationWorkflowEventRegisterer::registerEvents('tenant_deposited', TenantDepositedNotificationWorkflowEvent::class);

        self::$workflowEventsManager->get('tenant_notified')
            ->setTitle(bkntcsaas__('Tenant notified to subscribe to plan'))
            ->setEditAction('workflow_events', 'event_tenant_notified');
        NotificationWorkflowEventRegisterer::registerEvents('tenant_notified', TenantNotifiedNotificationWorkflowEvent::class);

        self::$workflowEventsManager->get('customer_signup')
            ->setTitle(bkntcsaas__('Customer signs up'))
            ->setAvailableParams([ 'customer_id' ]);

        self::$workflowEventsManager->get('customer_forgot_password')
            ->setTitle(bkntcsaas__('Customer forgot password'))
            ->setAvailableParams([ 'customer_id' ]);

        self::getWorkflowEventsManager()
            ->get('customer_reset_password')
            ->setTitle(bkntc__('Customer reset password'))
            ->setAvailableParams([ 'customer_id' ]);

        add_action('bkntcsaas_tenant_sign_up_confirm', function ($tenantId) {
            self::$workflowEventsManager->trigger('tenant_signup', [
                'tenant_id' => $tenantId,
            ]);
        }, 10, 2);

        add_action('bkntcsaas_tenant_sign_up_confirm_resend', function ($tenantId) {
            self::$workflowEventsManager->trigger('tenant_signup', [
                'tenant_id' => $tenantId,
            ]);
        }, 10, 2);

        add_action('bkntcsaas_tenant_deleted', function ($tenantId) {
            self::$workflowEventsManager->trigger('tenant_deleted', [ 'tenant_id' => $tenantId ], false, true);
        }, 10);

        add_action('bkntcsaas_tenant_paid', function ($tenantId) {
            $prev_tenantId = PermissionRegular::tenantId();
            PermissionRegular::setTenantId(null);

            self::$workflowEventsManager->trigger('tenant_paid', [
                'tenant_id' => $tenantId,
            ]);

            PermissionRegular::setTenantId($prev_tenantId);
        });

        add_action('bkntcsaas_tenant_deposit_paid', function ($billingId) {
            if (empty($billingId)) {
                return;
            }
            $billingInf = TenantBilling::noTenant()->get($billingId);

            if (empty($billingInf)) {
                return;
            }
            $prev_tenantId = PermissionRegular::tenantId();
            PermissionRegular::setTenantId(null);
            $amount     = $billingInf->amount;
            $tenantId   = $billingInf->tenant_id;
            self::$workflowEventsManager->trigger('tenant_deposited', [
                'tenant_id' => $tenantId,
                'deposit_amount' => $amount,
            ], false, true);

            PermissionRegular::setTenantId($prev_tenantId);
        });

        add_action('bkntcsaas_tenant_subscribed', function ($tenantId) {
            $prev_tenantId = PermissionRegular::tenantId();
            PermissionRegular::setTenantId(null);

            self::$workflowEventsManager->trigger('tenant_subscribed', [
                'tenant_id' => $tenantId
            ], false, true);

            PermissionRegular::setTenantId($prev_tenantId);
        });

        add_action('bkntcsaas_tenant_unsubscribed', function ($tenantId) {
            $prev_tenantId = PermissionRegular::tenantId();
            PermissionRegular::setTenantId(null);

            self::$workflowEventsManager->trigger('tenant_unsubscribed', [
                'tenant_id' => $tenantId
            ], false, true);

            PermissionRegular::setTenantId($prev_tenantId);
        });

        add_action('bkntcsaas_tenant_sign_up_completed', function ($tenantId) {
            self::$workflowEventsManager->trigger('tenant_signup_completed', [
                'tenant_id' => $tenantId
            ]);
        });

        add_action('bkntcsaas_tenant_reset_password', function ($tenantId) {
            self::$workflowEventsManager->trigger('tenant_forgot_password', [
                'tenant_id' => $tenantId
            ]);
        });

        add_action('bkntcsaas_tenant_reset_password_completed', function ($tenantId) {
            self::$workflowEventsManager->trigger('tenant_reset_password', [
                'tenant_id' => $tenantId
            ]);
        });

        add_action('bkntcsaas_tenant_notified', function ($tenantId) {
            $prev_tenantId = PermissionRegular::tenantId();

            PermissionRegular::setTenantId(null);

            self::$workflowEventsManager->trigger('tenant_notified', [ 'tenant_id' => $tenantId ]);

            PermissionRegular::setTenantId($prev_tenantId);
        });
    }

    /**
     * @return WorkflowEventsManager
     */
    public static function getWorkflowEventsManager()
    {
        return self::$workflowEventsManager;
    }

    private static function registerCoreWorkflowDrivers(): void
    {
        self::getWorkflowDriversManager()->register(new EmailWorkflowDriver());
        //        self::getWorkflowDriversManager()->register(new InAppNotification());
    }

    /**
     * @return WorkflowDriversManager
     */
    public static function getWorkflowDriversManager()
    {
        return self::$workflowDriversManager;
    }

    private static function registerCronActions(): void
    {
        Notifications::init();
    }

    private static function checkGmailSMTPCallback()
    {
        $gmail_smtp_redirect_uri = Helper::_get('gmail_smtp_saas', '', 'string');
        $authCode = Helper::_get('code', '', 'string');
        if (empty($gmail_smtp_redirect_uri) || empty($authCode)) {
            return;
        }

        $gmailService = new GoogleGmailService();
        $client = $gmailService->getClient();
        $client->fetchAccessTokenWithAuthCode($authCode);

        Helper::setOption('gmail_smtp_access_token', json_encode($client->getAccessToken()));
        Helper::redirect(admin_url('admin.php?page=' . Backend::getSlugName() . '&module=settings'));
    }

    public static function registerTextDomain()
    {
        add_action('plugins_loaded', function () {
            load_plugin_textdomain('booknetic-saas', false, 'booknetic-saas/languages');
        });
    }

    public static function registerCoreRoutes()
    {
        SaaSRoute::post('base', \BookneticSaaS\Backend\Base\Ajax::class);

        SaaSRoute::get('dashboard', \BookneticSaaS\Backend\Dashboard\Controller::class);

        SaaSRoute::get('tenants', \BookneticSaaS\Backend\Tenants\Controller::class);
        SaaSRoute::post('tenants', \BookneticSaaS\Backend\Tenants\Ajax::class);

        SaaSRoute::post('dashboard', \BookneticSaaS\Backend\Dashboard\Ajax::class);

        SaaSRoute::get('payments', \BookneticSaaS\Backend\Payments\Controller::class);
        SaaSRoute::post('payments', \BookneticSaaS\Backend\Payments\Ajax::class);

        SaaSRoute::get('plans', \BookneticSaaS\Backend\Plans\Controller::class);
        SaaSRoute::post('plans', \BookneticSaaS\Backend\Plans\Ajax::class);

        SaaSRoute::get('custom-fields', \BookneticSaaS\Backend\Customfields\Controller::class);
        SaaSRoute::post('custom-fields', \BookneticSaaS\Backend\Customfields\Ajax::class);

        SaaSRoute::get('workflow', new \BookneticApp\Backend\Workflow\Controller(self::getWorkflowEventsManager()));
        SaaSRoute::post('workflow', new \BookneticApp\Backend\Workflow\Ajax(self::getWorkflowEventsManager()));
        SaaSRoute::post('workflow_events', new \BookneticApp\Backend\Workflow\EventsAjax(self::getWorkflowEventsManager()));

        SaaSRoute::get('settings', \BookneticSaaS\Backend\Settings\Controller::class);

        SaaSRoute::post('settings', new \BookneticSaaS\Backend\Settings\Ajax(self::getWorkflowEventsManager()));

        SaaSRoute::get('boostore', \BookneticApp\Backend\Boostore\Controller::class);
        SaaSRoute::post('boostore', \BookneticApp\Backend\Boostore\Ajax::class);

        SaaSRoute::post('workflow_actions', new \BookneticSaaS\Backend\Settings\Ajax(self::getWorkflowEventsManager()));

        SaaSRoute::get('cart', \BookneticApp\Backend\Boostore\CartController::class);
    }

    public static function registerCoreMenus()
    {
        SaaSMenuUI::get('dashboard')
            ->setTitle(bkntcsaas__('Dashboard'))
            ->setIcon('fa fa-cube')
            ->setPriority(100);

        SaaSMenuUI::get('tenants')
            ->setTitle(bkntcsaas__('Tenants'))
            ->setIcon('fa fa-user-tie')
            ->setPriority(200);

        SaaSMenuUI::get('payments')
            ->setTitle(bkntcsaas__('Payments'))
            ->setIcon('fa fa-credit-card')
            ->setPriority(300);

        SaaSMenuUI::get('plans')
            ->setTitle(bkntcsaas__('Plans'))
            ->setIcon('fa fa-rocket')
            ->setPriority(400);

        SaaSMenuUI::get('custom-fields')
            ->setTitle(bkntcsaas__('Custom fields'))
            ->setIcon('fa fa-magic')
            ->setPriority(500);

        SaaSMenuUI::get('workflow')
            ->setTitle(bkntcsaas__('Workflows'))
            ->setIcon('fa fa-project-diagram')
            ->setPriority(600);

        SaaSMenuUI::get('settings')
            ->setTitle(bkntcsaas__('Settings'))
            ->setIcon('fa fa-cog')
            ->setPriority(1000);

        SaaSMenuUI::get('back_to_wordpress', AbstractMenuUI::MENU_TYPE_TOP_LEFT)
            ->setTitle(bkntcsaas__('WORDPRESS'))
            ->setIcon('fa fa-angle-left')
            ->setLink(admin_url())
            ->setPriority(100);

        SaaSMenuUI::get('boostore', AbstractMenuUI::MENU_TYPE_BOOSTORE)
            ->setTitle(bkntcsaas__('Boostore'))
            ->setIcon(Helper::icon('store.svg'))
            ->setPriority(200);
    }

    public static function registerTenantActivities()
    {
        if (PermissionRegular::isAdministrator()) {
            Route::get('billing', Controller::class);
            Route::post('billing', Ajax::class);

            MenuUI::get('billing')
                ->setTitle(bkntc__('Billing'))
                ->setIcon('fa fa-credit-card')
                ->setPriority(101);
        }

        if (Route::getCurrentModule() == 'dashboard' && !Capabilities::tenantCan('dashboard')) {
            \BookneticApp\Providers\Helpers\Helper::redirect(Route::getURL('billing'));
        }
    }

    public static function tenantCapabilities($can, $capability)
    {
        $tenantInf = PermissionRegular::tenantInf();

        Tenant::onUpdating([ TenantHelper::class, 'revertRestrictedLimits' ]);

        if (! $tenantInf) {
            return $can;
        }

        if (! array_key_exists($tenantInf->id, self::$planCaches)) {
            if (Date::epoch(Date::dateSQL()) > Date::epoch($tenantInf->expires_in) && ! Tenant::haveEnoughBalanceToPay()) {
                $plan = Plan::where('expire_plan', 1)->fetch();
            } else {
                $plan = $tenantInf->plan()->fetch();
            }
            self::$planCaches[ $tenantInf->id ] = $plan;

            $permissions = json_decode($plan->permissions, true);
            if ($plan->expire_plan == 1) {
                TenantHelper::restrictLimits($tenantInf->id, $permissions);
            }
        } else {
            $plan = self::$planCaches[ $tenantInf->id ];
        }

        if (! $plan) {
            return false;
        }

        $permissions = json_decode($plan->permissions, true);

        if (! isset($permissions[ 'capabilities' ][ $capability ]) || $permissions[ 'capabilities' ][ $capability ] === 'off') {
            return false;
        }

        return $can;
    }

    public static function tenantLimits($limit, $limitName)
    {
        $tenantInf = PermissionRegular::tenantInf();

        if (! $tenantInf) {
            return $limit;
        }

        if (Date::epoch(Date::dateSQL()) > Date::epoch($tenantInf->expires_in) && ! Tenant::haveEnoughBalanceToPay()) {
            $plan = Plan::where('expire_plan', 1)->fetch();
        } else {
            $plan = $tenantInf->plan()->fetch();
        }

        if (! $plan) {
            return 0;
        }

        $permissions = json_decode($plan->permissions, true);

        if (! isset($permissions[ 'limits' ][ $limitName ])) {
            return 0;
        }

        return (int) $permissions[ 'limits' ][ $limitName ];
    }
}

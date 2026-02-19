<?php

namespace BookneticSaaS\Backend\Settings;

use BookneticApp\Backend\Settings\Helpers\LocalizationService;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\Workflow;
use BookneticApp\Models\WorkflowAction;
use BookneticApp\Providers\Common\ShortCodeService;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Request\Post;
use BookneticSaas\Backend\Notifications\Registerer\NotificationWorkflowEventRegisterer;
use BookneticSaaS\Backend\Settings\Exceptions\SettingsNotFoundException;
use BookneticSaaS\Backend\Settings\Exceptions\SplitPaymentNotSupportedException;
use BookneticSaaS\Models\Plan;
use BookneticSaaS\Models\Tenant;
use BookneticSaaS\Providers\Common\EmailWorkflowDriver;
use BookneticSaaS\Providers\Common\GoogleGmailService;
use BookneticSaaS\Providers\Core\Permission;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Providers\UI\TabUI;
use BookneticVendor\Google\Service\Oauth2;
use Exception;

class Ajax extends \BookneticApp\Providers\Core\Controller
{
    private $workflowEventsManager;

    public function __construct($workflowEventsManager)
    {
        $this->workflowEventsManager = $workflowEventsManager;
    }

    public function general_settings()
    {
        if (! Permission::canUseBooknetic()) {
            throw new SettingsNotFoundException();
        }

        $getConfirmationNumber = DB::DB()->get_row('SELECT `AUTO_INCREMENT` FROM  `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_SCHEMA`=database() AND `TABLE_NAME`=\'' . DB::table(Appointment::getTableName()) . '\'', ARRAY_A);

        return $this->modalView('general_settings', [
            'confirmation_number' => ( int ) $getConfirmationNumber[ 'AUTO_INCREMENT' ]
        ]);
    }

    public function plan_settings()
    {
        if (!Permission::canUseBooknetic()) {
            throw new SettingsNotFoundException();
        }

        $plans = Plan::orderBy('order_by')->fetchAll();

        $expirePlan = Plan::where('expire_plan', 1)->fetch();

        $defaultPlan = Plan::query()->where('is_default', 1)->fetch();

        $annualPlanBadgeText   = Helper::getOption('annual_plan_badge_text', '');
        $annualPlanBadgeColor  = Helper::getOption('annual_plan_badge_color', '');
        $isAnnualPlanBadgeEnabled = Helper::getOption('is_annual_plan_badge_enabled', 0) == 1;

        return $this->modalView('plan_settings', [
            'plans' => $plans,
            'trial_period' => (int) Helper::getOption('trial_period', 30),
            'default_interval_on_pricing' => Helper::getOption('default_interval_on_pricing', 'monthly'),
            'show_monthly_breakdown_on_annual' => Helper::getOption('show_monthly_breakdown_on_annual', 'off'),
            'expire_plan_id' => (int)$expirePlan->id,
            'trial_plan_id' => (int)$defaultPlan->id,
            'annual_plan_badge_text' => $annualPlanBadgeText,
            'annual_plan_badge_color' => $annualPlanBadgeColor,
            'is_annual_plan_badge_enabled' => $isAnnualPlanBadgeEnabled
        ]);
    }

    public function save_plan_settings()
    {
        $trialPlanId  = Post::int('trial_plan_id');
        $expirePlanId = Post::int('expire_plan_id');
        $trialPeriod   = Post::int('trial_period', 30);
        $isAnnualPlanBadgeEnabled = Post::int('is_annual_plan_badge_enabled', 0, [0, 1]);
        $annualPlanBadgeText = Post::string('annual_plan_badge_text');
        $annualPlanBadgeColor = Post::string('annual_plan_badge_color');

        $defaultIntervalOnPricing = Post::string(
            'default_interval_on_pricing',
            'monthly',
            ['monthly', 'annual']
        );

        $showMonthlyBreakdown = Post::string(
            'show_monthly_breakdown_on_annual',
            'off',
            ['on', 'off']
        );

        Helper::setOption('trial_period', $trialPeriod);
        Helper::setOption('default_interval_on_pricing', $defaultIntervalOnPricing);
        Helper::setOption('show_monthly_breakdown_on_annual', $showMonthlyBreakdown);
        Helper::setOption('is_annual_plan_badge_enabled', $isAnnualPlanBadgeEnabled);
        Helper::setOption('annual_plan_badge_text', $annualPlanBadgeText);
        Helper::setOption('annual_plan_badge_color', $annualPlanBadgeColor);

        if ($trialPlanId > 0) {
            $plan = Plan::query()->get($trialPlanId);
            if ($plan) {
                Plan::query()->where('is_default', 1)->update(['is_default' => 0]);
                Plan::query()->where('id', $trialPlanId)->update(['is_default' => 1]);
            }
        }

        if ($expirePlanId > 0) {
            $plan = Plan::query()->get($expirePlanId);
            if ($plan) {
                Plan::query()->where('expire_plan', 1)->update(['expire_plan' => 0]);
                Plan::query()->where('id', $expirePlanId)->update(['expire_plan' => 1]);
            }
        }

        return $this->response(true);
    }

    public function whitelabel_settings()
    {
        if (! Permission::canUseBooknetic()) {
            throw new SettingsNotFoundException();
        }

        return $this->modalView('whitelabel_settings', []);
    }

    public function page_settings()
    {
        if (! Permission::canUseBooknetic()) {
            throw new SettingsNotFoundException();
        }

        return $this->modalView('page_settings', []);
    }
    public function payments_settings()
    {
        if (! Permission::canUseBooknetic()) {
            throw new SettingsNotFoundException();
        }

        return $this->modalView('payments_settings', [
            'currencies' => Helper::currencies(),
            'currency' => Helper::currencySymbol()
        ]);
    }

    public function email_settings()
    {
        if (! Permission::canUseBooknetic()) {
            return $this->response(false, bkntcsaas__('Selected settings not found!'));
        }

        $accessToken  = Helper::getOption('gmail_smtp_access_token', '');

        if (empty($accessToken)) {
            return $this->modalView('email_settings', [
                'authorized' => false,
                'email' => ''
            ]);
        }

        $gmailService = new GoogleGmailService();
        $client = $gmailService->getClient();

        $client->setAccessToken($accessToken);
        $Oauth2 = new Oauth2($client);

        $errors = [];

        try {
            $userInfo = $Oauth2->userinfo->get();
        } catch (Exception $e) {
            $errors[] = json_decode($e->getMessage(), true);
        }

        return $this->modalView('email_settings', [
            'authorized' => true,
            'email' => ! empty($userInfo) ? $userInfo->email : '',
            'errors' => $errors
        ]);
    }

    public function logout_gmail()
    {
        if (! Permission::canUseBooknetic()) {
            throw new SettingsNotFoundException();
        }
        Helper::deleteOption('gmail_smtp_access_token', false);

        return $this->response(true);
    }

    public function payment_gateways_settings()
    {
        if (! Permission::canUseBooknetic()) {
            throw new SettingsNotFoundException();
        }

        return $this->modalView('payment_gateways_settings', []);
    }

    public function payment_split_payments_settings()
    {
        if (! Permission::canUseBooknetic()) {
            throw new SettingsNotFoundException();
        }

        if (! Permission::canUseSplitPayments()) {
            throw new SplitPaymentNotSupportedException();
        }

        return $this->modalView('payment_split_payments_settings', [ 'gateways' => TabUI::get('payment_split_payments_settings')->getSubItems() ]);
    }

    public function integrations_facebook_api_settings()
    {
        if (! Permission::canUseBooknetic()) {
            throw new SettingsNotFoundException();
        }

        return $this->modalView('integrations_facebook_api_settings', []);
    }

    public function integrations_google_login_settings()
    {
        if (! Permission::canUseBooknetic()) {
            throw new SettingsNotFoundException();
        }

        return $this->modalView('integrations_google_login_settings', []);
    }

    public function workflow_action_edit_view()
    {
        $id = Helper::_post('id', 0, 'int');

        $workflowActionInfo = WorkflowAction::get($id);
        if (! $workflowActionInfo) {
            return $this->response(false);
        }

        $data = json_decode($workflowActionInfo->data, true);

        $availableParams = $this->workflowEventsManager->get(Workflow::get($workflowActionInfo->workflow_id)[ 'when' ])
            ->getAvailableParams();

        $toShortcodes = $this->workflowEventsManager->getShortcodeService()->getShortCodesList($availableParams, [ 'email' ]);
        $subjectAndBodyShortcodes = $this->workflowEventsManager->getShortcodeService()->getShortCodesList($availableParams);
        $attachmentShortcodes = $this->workflowEventsManager->getShortcodeService()->getShortCodesList($availableParams, [ 'file', 'url' ]);

        $data[ 'attachments_value' ] = isset($data[ 'attachments' ]) ? explode(',', $data[ 'attachments' ]) : [];
        $data[ 'to_value' ] = isset($data[ 'to' ]) ? explode(',', $data[ 'to' ]) : [];

        $toAllShortcodeList = $this->shortcodeListGenerate($toShortcodes, $data[ 'to_value' ]);
        $attachmentAllShortcodeList = $this->shortcodeListGenerate($attachmentShortcodes, $data[ 'attachments_value' ]);

        return $this->modalView('workflow_action_edit', [
            'action_info' => $workflowActionInfo,
            'data' => $data,
            'to_shortcodes' => $toAllShortcodeList,
            'all_shortcodes' => $subjectAndBodyShortcodes,
            'attachment_shortcodes' => $attachmentAllShortcodeList,
        ], [ 'workflow_action_id' => $id ]);
    }

    /* save */

    private function shortcodeListGenerate($shortcodeList, $shortcodeDbValue)
    {
        $list = [];

        foreach ($shortcodeList as $value) {
            $list[ '{' . $value[ 'code' ] . '}' ][ 'value' ] = $value[ 'name' ];
        }

        foreach ($shortcodeDbValue as $value) {
            if (empty($value)) {
                continue;
            }

            if (! array_key_exists($value, $list)) {
                $list[ $value ][ 'value' ] = $value;
            }

            $list[ $value ][ 'selected' ] = true;
        }

        return $list;
    }

    public function save_general_settings()
    {
        $google_maps_api_key            = Helper::_post('google_maps_api_key', '', 'string');
        $google_maps_map_id             = Helper::_post('google_maps_map_id', '', 'string');
        $google_recaptcha               = Helper::_post('google_recaptcha', 'off', 'string', [ 'on', 'off' ]);
        $google_recaptcha_site_key      = Helper::_post('google_recaptcha_site_key', '', 'string');
        $google_recaptcha_secret_key    = Helper::_post('google_recaptcha_secret_key', '', 'string');
        $confirmation_number            = Helper::_post('confirmation_number', '', 'int');
        $enable_language_switcher       = Helper::_post('enable_language_switcher', 'off', 'string', [ 'on', 'off' ]);
        $active_languages               = Helper::_post('active_languages', [], 'arr');
        $new_wp_user_on_new_booking     = Helper::_post('new_wp_user_on_new_booking', 'off', 'string', [ 'on', 'off' ]);
        $disallow_tenants_to_enter_wp_dashboard =
            Post::string('disallow_tenants_to_enter_wp_dashboard', 'off', [ 'on', 'off' ]);

        if ($enable_language_switcher === 'off') {
            $active_languages = [];
        }

        Helper::setOption('google_maps_api_key', $google_maps_api_key);
        Helper::setOption('google_maps_map_id', $google_maps_map_id);
        Helper::setOption('google_recaptcha', $google_recaptcha);
        Helper::setOption('google_recaptcha_site_key', $google_recaptcha_site_key);
        Helper::setOption('google_recaptcha_secret_key', $google_recaptcha_secret_key);
        Helper::setOption('enable_language_switcher', $enable_language_switcher);
        Helper::setOption('new_wp_user_on_new_booking', $new_wp_user_on_new_booking);
        Helper::setOption('disallow_tenants_to_enter_wp_dashboard', $disallow_tenants_to_enter_wp_dashboard);

        if ($confirmation_number > 10000000) {
            return $this->response(false, bkntcsaas__('Confirmation number is invalid!'));
        }

        if ($confirmation_number > 0) {
            $getConfirmationNumber = DB::DB()->get_row(
                'SELECT `AUTO_INCREMENT`
             FROM `INFORMATION_SCHEMA`.`TABLES`
             WHERE `TABLE_SCHEMA` = database()
             AND `TABLE_NAME` = \'' . DB::table(Appointment::getTableName()) . '\'',
                ARRAY_A
            );

            if ((int)$getConfirmationNumber['AUTO_INCREMENT'] > $confirmation_number) {
                return $this->response(false, bkntcsaas__('Confirmation number is invalid!'));
            }

            DB::DB()->query(
                "ALTER TABLE `" . DB::table(Appointment::getTableName()) . "` AUTO_INCREMENT=" . (int)$confirmation_number
            );
        }

        $active_languages_arr = [];
        foreach ($active_languages as $active_language) {
            if (
                is_string($active_language) &&
                !empty($active_language) &&
                LocalizationService::isLngCorrect($active_language)
            ) {
                $active_languages_arr[] = (string)$active_language;
            }
        }

        Helper::setOption('active_languages', $active_languages_arr);

        return $this->response(true);
    }

    public function save_whitelabel_settings()
    {
        if (Permission::isDemoVersion()) {
            return $this->response(false, "You can't made any changes in the settings because it is a demo version.");
        }

        $backend_title = Helper::_post('backend_title', '', 'string');
        $backend_slug = Helper::_post('backend_slug', '', 'string');
        $powered_by = Helper::_post('powered_by', '', 'string');
        $documentation_url = Helper::_post('documentation_url', '', 'string');
        $customCss = Helper::_post('custom_css', '', 'string');
        $whitelabel_logo = '';

        if (empty($backend_slug)) {
            return $this->response(false, bkntcsaas__('The Backend Slug can not be empty!'));
        }

        if (isset($_FILES[ 'whitelabel_logo' ]) && is_string($_FILES[ 'whitelabel_logo' ][ 'tmp_name' ])) {
            $path_info = pathinfo($_FILES[ "whitelabel_logo" ][ "name" ]);
            $extension = strtolower($path_info[ 'extension' ]);

            if (! in_array($extension, [ 'jpg', 'jpeg', 'png', 'svg' ])) {
                return $this->response(false, bkntcsaas__('Only JPG, PNG and SVG images allowed!'));
            }

            $whitelabel_logo = md5(base64_encode(rand(1, 9999999) . microtime(true))) . '.' . $extension;
            $file_name = \BookneticApp\Providers\Helpers\Helper::uploadedFile($whitelabel_logo, 'Base');

            $oldFileName = Helper::getOption('whitelabel_logo');
            if (! empty($oldFileName)) {
                $oldFileFullPath = \BookneticApp\Providers\Helpers\Helper::uploadedFile($oldFileName, 'Base');

                if (is_file($oldFileFullPath) && is_writable($oldFileFullPath)) {
                    unlink($oldFileFullPath);
                }
            }

            move_uploaded_file($_FILES[ 'whitelabel_logo' ][ 'tmp_name' ], $file_name);
        }
        if ($whitelabel_logo != '') {
            Helper::setOption('whitelabel_logo', $whitelabel_logo);
        }

        $whitelabel_logo_sm = '';
        if (isset($_FILES[ 'whitelabel_logo_sm' ]) && is_string($_FILES[ 'whitelabel_logo_sm' ][ 'tmp_name' ])) {
            $path_info = pathinfo($_FILES[ "whitelabel_logo_sm" ][ "name" ]);
            $extension = strtolower($path_info[ 'extension' ]);

            if (! in_array($extension, [ 'jpg', 'jpeg', 'png', 'svg' ])) {
                return $this->response(false, bkntcsaas__('Only JPG, PNG and SVG images allowed!'));
            }

            $whitelabel_logo_sm = md5(base64_encode(rand(1, 9999999) . microtime(true))) . '.' . $extension;
            $file_name = \BookneticApp\Providers\Helpers\Helper::uploadedFile($whitelabel_logo_sm, 'Base');

            $oldFileName = Helper::getOption('whitelabel_logo_sm');
            if (! empty($oldFileName)) {
                $oldFileFullPath = \BookneticApp\Providers\Helpers\Helper::uploadedFile($oldFileName, 'Base');

                if (is_file($oldFileFullPath) && is_writable($oldFileFullPath)) {
                    unlink($oldFileFullPath);
                }
            }

            move_uploaded_file($_FILES[ 'whitelabel_logo_sm' ][ 'tmp_name' ], $file_name);
        }
        if ($whitelabel_logo_sm != '') {
            Helper::setOption('whitelabel_logo_sm', $whitelabel_logo_sm);
        }

        Helper::setOption('backend_title', $backend_title);
        Helper::setOption('backend_slug', $backend_slug);
        Helper::setOption('documentation_url', $documentation_url);
        Helper::setOption('powered_by', $powered_by);
        $cleanCss = preg_replace('/<\/?[^>]+>/', '', $customCss);
        Helper::setOption('custom_css', trim($cleanCss));

        return $this->response(true);
    }

    public function save_page_settings()
    {
        if (Permission::isDemoVersion()) {
            return $this->response(false, "You can't made any changes in the settings because it is a demo version.");
        }

        $sign_in_page = Helper::_post('sign_in_page', null, 'int');
        $sign_up_page = Helper::_post('sign_up_page', null, 'int');
        $booking_page = Helper::_post('booking_page', null, 'int');
        $forgot_password_page = Helper::_post('forgot_password_page', null, 'int');
        $change_status_page_id = Helper::_post('change_status_page_id', '', 'int');

        $regular_sign_in_page = Helper::_post('regular_sing_in_page', null, 'int');
        $regular_sign_up_page = Helper::_post('regular_sign_up_page', null, 'int');
        $regular_forgot_password_page = Helper::_post('regular_forgot_password_page', null, 'int');

        Helper::setOption('sign_in_page', $sign_in_page);
        Helper::setOption('sign_up_page', $sign_up_page);
        Helper::setOption('booking_page', $booking_page);
        Helper::setOption('forgot_password_page', $forgot_password_page);
        Helper::setOption('change_status_page_id', $change_status_page_id);

        Helper::setOption('regular_sing_in_page', $regular_sign_in_page);
        Helper::setOption('regular_sign_up_page', $regular_sign_up_page);
        Helper::setOption('regular_forgot_password_page', $regular_forgot_password_page);

        return $this->response(true);
    }

    public function save_email_settings()
    {
        if (Permission::isDemoVersion()) {
            return $this->response(false, "You can't made any changes in the settings because it is a demo version.");
        }

        $mail_gateway = Helper::_post('mail_gateway', '', 'string');
        $smtp_hostname = Helper::_post('smtp_hostname', '', 'string');
        $smtp_port = Helper::_post('smtp_port', '', 'string');
        $smtp_secure = Helper::_post('smtp_secure', '', 'string');
        $smtp_username = Helper::_post('smtp_username', '', 'string');
        $smtp_password = Helper::_post('smtp_password', '', 'string');
        $gmail_smtp_client_id = Helper::_post('gmail_smtp_client_id', '', 'string');
        $gmail_smtp_client_secret = Helper::_post('gmail_smtp_client_secret', '', 'string');
        $sender_email = Helper::_post('sender_email', '', 'string');
        $sender_name = Helper::_post('sender_name', '', 'string');

        if ($mail_gateway !== 'smtp') {
            $smtp_hostname = '';
            $smtp_port = '';
            $smtp_secure = '';
            $smtp_username = '';
            $smtp_password = '';
        } elseif ((empty($smtp_hostname) || empty($smtp_port) || ! is_numeric($smtp_port) || empty($smtp_secure) || ! in_array($smtp_secure, [ 'tls', 'ssl', 'no' ]) || empty($smtp_username))) {
            return $this->response(false, bkntcsaas__('Please fill the SMTP credentials!'));
        } elseif ($mail_gateway === 'gmail_smtp' && (empty($gmail_smtp_client_id) || empty($gmail_smtp_client_secret))) {
            return $this->response(false, bkntcsaas__('Please fill the Gmail SMTP credentials!'));
        }

        if (empty($sender_name)) {
            return $this->response(false, bkntcsaas__('Please type the sender name field!'));
        }

        if (empty($sender_email) || ! filter_var($sender_email, FILTER_VALIDATE_EMAIL)) {
            return $this->response(false, bkntcsaas__('Please type the sender email field!'));
        }

        Helper::setOption('mail_gateway', $mail_gateway);
        Helper::setOption('smtp_hostname', $smtp_hostname);
        Helper::setOption('smtp_port', $smtp_port);
        Helper::setOption('smtp_secure', $smtp_secure);
        Helper::setOption('smtp_username', $smtp_username);
        Helper::setOption('smtp_password', $smtp_password);
        Helper::setOption('gmail_smtp_client_id', $gmail_smtp_client_id);
        Helper::setOption('gmail_smtp_client_secret', $gmail_smtp_client_secret);
        Helper::setOption('sender_email', $sender_email);
        Helper::setOption('sender_name', $sender_name);

        return $this->response(true);
    }

    public function save_payments_settings()
    {
        $currency = Helper::_post('currency', 'USD', 'string');
        $currency_format = Helper::_post('currency_format', '1', 'int');
        $currency_symbol = Helper::_post('currency_symbol', '', 'string');
        $tenant_default_currency = Helper::_post('tenant_default_currency', 'USD', 'string');
        $tenant_default_currency_format = Helper::_post('tenant_default_currency_format', '1', 'int');
        $tenant_default_currency_symbol = Helper::_post('tenant_default_currency_symbol', '', 'string');
        $price_number_format = Helper::_post('price_number_format', '1', 'int');
        $price_number_of_decimals = Helper::_post('price_number_of_decimals', '2', 'int');

        $currencyInf = Helper::currencies($currency);
        if (! $currencyInf) {
            $currency = 'USD';
        }

        if (empty($currency_symbol)) {
            $currency_symbol = '$';
        }

        if (! Helper::currencies($tenant_default_currency)) {
            $tenant_default_currency = "USD";
        }

        if (empty($tenant_default_currency_symbol)) {
            $tenant_default_currency_symbol = '$';
        }

        Helper::setOption('currency', $currency);
        Helper::setOption('currency_format', $currency_format);
        Helper::setOption('currency_symbol', $currency_symbol);
        Helper::setOption('tenant_default_currency', $tenant_default_currency);
        Helper::setOption('tenant_default_currency_format', $tenant_default_currency_format);
        Helper::setOption('tenant_default_currency_symbol', $tenant_default_currency_symbol);
        Helper::setOption('price_number_format', $price_number_format);
        Helper::setOption('price_number_of_decimals', $price_number_of_decimals);

        return $this->response(true);
    }

    public function save_payment_gateways_settings()
    {
        if (Permission::isDemoVersion()) {
            return $this->response(false, "You can't make any changes in the settings because it is a demo version.");
        }

        $paypal_enable = Helper::_post('paypal_enable', 'off', 'string', [ 'on', 'off' ]);
        $stripe_enable = Helper::_post('stripe_enable', 'off', 'string', [ 'on', 'off' ]);
        $woocommerce_enable = Helper::_post('woocommerce_enable', 'off', 'string', [ 'on', 'off' ]);

        $paypal_client_id = Helper::_post('paypal_client_id', '', 'string');
        $paypal_client_secret = Helper::_post('paypal_client_secret', '', 'string');
        $paypal_webhook_id = Helper::_post('paypal_webhook_id', '', 'string');
        $paypal_mode = Helper::_post('paypal_mode', 'sandbox', 'string', [ 'sandbox', 'live' ]);

        $stripe_client_id = Helper::_post('stripe_client_id', '', 'string');
        $stripe_client_secret = Helper::_post('stripe_client_secret', '', 'string');
        $stripe_webhook_secret = Helper::_post('stripe_webhook_secret', '', 'string');

        $woocommerce_tenant_redirect_to = Helper::_post('woocommerce_tenant_redirect_to', 'cart', 'string', [ 'cart', 'checkout' ]);
        $woocommerce_tenant_order_statuses = Helper::_post('woocommerce_tenant_order_statuses', '', 'string');

        $payment_gateways_arr = Helper::_post('payment_gateways_order', '', 'string');

        $payment_gateways = [];
        $payment_gateways_arr = json_decode($payment_gateways_arr, true);

        if (! is_array($payment_gateways_arr)) {
            return $this->response(false);
        }

        if ($woocommerce_enable === 'on' && ! class_exists('woocommerce')) {
            return $this->response(false, bkntcsaas__('For using WooCommerce as a payment method, you should install and enable it on WordPress plugins!'));
        }

        $payment_gateways_by_order = [];
        $allowed_payment_gateways = [ 'stripe', 'paypal', 'woocommerce' ];
        foreach ($payment_gateways_arr as $ordr => $gateway) {
            if (is_string($gateway) && in_array($gateway, [ 'stripe', 'paypal', 'woocommerce' ])) {
                if (isset($payment_gateways_by_order[ $gateway ])) {
                    return $this->response(false);
                }

                $payment_gateways[] = $gateway;
                $payment_gateways_by_order[ $gateway ] = $ordr;
            } else {
                return $this->response(false);
            }
        }

        if (count($payment_gateways) != count($allowed_payment_gateways)) {
            return $this->response(false);
        }

        Helper::setOption('paypal_enable', $paypal_enable);
        Helper::setOption('stripe_enable', $stripe_enable);
        Helper::setOption('woocommerce_enable', $woocommerce_enable);

        Helper::setOption('stripe_client_id', $stripe_client_id);
        Helper::setOption('stripe_client_secret', $stripe_client_secret);
        Helper::setOption('stripe_webhook_secret', $stripe_webhook_secret);

        Helper::setOption('paypal_client_id', $paypal_client_id);
        Helper::setOption('paypal_client_secret', $paypal_client_secret);
        Helper::setOption('paypal_webhook_id', $paypal_webhook_id);
        Helper::setOption('paypal_mode', $paypal_mode);

        Helper::setOption('woocommerce_tenant_redirect_to', $woocommerce_tenant_redirect_to);
        Helper::setOption('woocommerce_tenant_order_statuses', $woocommerce_tenant_order_statuses);

        Helper::setOption('payment_gateways_order', implode(',', $payment_gateways));

        return $this->response(true);
    }

    public function save_integrations_facebook_api_settings()
    {
        $facebook_login_enable = Helper::_post('facebook_login_enable', 'off', 'string', [ 'on', 'off' ]);
        $facebook_app_id = Helper::_post('facebook_app_id', '', 'string');
        $facebook_app_secret = Helper::_post('facebook_app_secret', '', 'string');

        if ($facebook_login_enable === 'on' && (empty($facebook_app_id) || empty($facebook_app_secret))) {
            return $this->response(false, bkntcsaas__('Please fill in all required fields correctly!'));
        }

        Helper::setOption('facebook_login_enable', $facebook_login_enable);
        Helper::setOption('facebook_app_id', $facebook_app_id);
        Helper::setOption('facebook_app_secret', $facebook_app_secret);

        return $this->response(true);
    }

    public function save_integrations_google_login_settings()
    {
        $google_login_enable = Helper::_post('google_login_enable', 'off', 'string', [ 'on', 'off' ]);
        $google_login_app_id = Helper::_post('google_login_app_id', '', 'string');
        $google_login_app_secret = Helper::_post('google_login_app_secret', '', 'string');

        if ($google_login_enable === 'on' && (empty($google_login_app_id) || empty($google_login_app_secret))) {
            return $this->response(false, bkntcsaas__('Please fill in all required fields correctly!'));
        }

        Helper::setOption('google_login_enable', $google_login_enable);
        Helper::setOption('google_login_app_id', $google_login_app_id);
        Helper::setOption('google_login_app_secret', $google_login_app_secret);

        return $this->response(true);
    }

    public function workflow_action_save_data()
    {
        $id = Helper::_post('id', 0, 'int');
        $to = Helper::_post('to', '', 'string');
        $subject = Helper::_post('subject', '', 'string');
        $body = Helper::_post('body', '', 'string');
        $attachments = Helper::_post('attachments', '', 'string');
        $is_active = Helper::_post('is_active', 1, 'num');

        if (! WorkflowAction::get($id)) {
            return $this->response(false);
        }

        $newData = [
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'attachments' => $attachments
        ];

        WorkflowAction::where('id', $id)->update([ 'data' => json_encode($newData), 'is_active' => $is_active ]);

        return $this->response(true);
    }

    public function workflow_action_send_test_data()
    {
        $to = Helper::_post('to', '', 'string');
        $actionId = Helper::_post('id', 0, 'int');

        if (! empty($to) && $actionId > 0) {
            $actionInf = WorkflowAction::get($actionId);
            $settings = json_decode($actionInf->data, true);
            $settings[ 'to' ] = $to;
            $actionInf->data = json_encode($settings);
            $actionInf->when = 'send_test';
            $driver = new EmailWorkflowDriver();
            $driver->handle(new Collection(), $actionInf, new ShortCodeService());
        }

        return $this->response(true);
    }

    public function gmail_smtp_login()
    {
        $mail_gateway = Helper::_post('mail_gateway', '', 'string');
        $gmail_smtp_client_id = Helper::_post('gmail_smtp_client_id', '', 'string');
        $gmail_smtp_client_secret = Helper::_post('gmail_smtp_client_secret', '', 'string');
        $sender_email = Helper::_post('sender_email', '', 'string');
        $sender_name = Helper::_post('sender_name', '', 'string');

        Helper::setOption('gmail_smtp_client_id', $gmail_smtp_client_id);
        Helper::setOption('gmail_smtp_client_secret', $gmail_smtp_client_secret);
        Helper::setOption('sender_email', $sender_email);
        Helper::setOption('sender_name', $sender_name);
        Helper::setOption('mail_gateway', $mail_gateway);

        $service = new GoogleGmailService();
        $client = $service->getClient();
        $authUrl = $client->createAuthUrl();

        return $this->response(true, [ 'redirect_url' => $authUrl ]);
    }

    /**
     * @return mixed|null
     * @throws SplitPaymentNotSupportedException
     */
    public function save_payment_split_payments_settings()
    {
        if (! Permission::canUseSplitPayments()) {
            throw new SplitPaymentNotSupportedException();
        }

        $payment_gateways_arr = Helper::_post('payment_gateways_order', '', 'string');
        $gateway_statuses = Helper::_post('gateways_statuses');

        $payment_gateways_arr = json_decode($payment_gateways_arr, true);

        if (! is_array($payment_gateways_arr)) {
            return $this->response(false);
        }

        if ($gateway_statuses) {
            foreach ($gateway_statuses as $slug => $status) {
                if (true) {
                    Helper::setOption($slug . '_payment_enabled', $status);
                }
            }
        }

        return $this->response(true);
    }

    public function in_app_notification_view()
    {
        $id = Post::int('id');
        $action = Post::string('event');

        $workflowActionInfo = WorkflowAction::query()->get($id);

        if (! $workflowActionInfo) {
            return $this->response(false);
        }

        if (NotificationWorkflowEventRegisterer::getEventInstance($action) === null) {
            return $this->response(false, ['error_msg' => 'In App Notification driver not supported for this event']);
        }

        $availableParams = $this->workflowEventsManager->get(Workflow::get($workflowActionInfo->workflow_id)['when'])
            ->getAvailableParams();

        $toShortcodes = $this->workflowEventsManager->getShortcodeService()->getShortCodesList($availableParams, ['email']);
        $subjectAndBodyShortcodes   = $this->workflowEventsManager->getShortcodeService()->getShortCodesList($availableParams);
        $idShortcodes = $this->workflowEventsManager->getShortcodeService()->getShortCodesList($availableParams, ['staff_id']);

        $data = json_decode($workflowActionInfo->data, true);

        $selectedIds = isset($data['to']) ? explode(',', $data['to']) : [];

        $users = Tenant::query()->select(['user_id as id', 'full_name as name'])->where('user_id', '<>', null)->fetchAll();

        return $this->modalView('in_app_notification_view', [
            'action_info' => $workflowActionInfo,
            'users' => $users,
            'to' => $selectedIds,
            'title' => $data['title'] ?? null,
            'toShortcodes' => $toShortcodes,
            'all_shortcodes' => $subjectAndBodyShortcodes,
            'message' => $data['message'] ?? null,
            'status' => $data['status'] ?? null,
            'run_workflows' => $data['run_workflows'] ?? true
        ], [
            'workflow_action_id' => $id,
        ]);
    }

    public function in_app_notification_save()
    {
        $id = Post::int('id');
        $to = Post::string('to');
        $title = Post::string('title');
        $message = Post::string('message');
        $status = Post::string('status');
        $is_active = Post::int('is_active');
        $run_workflows = Post::int('run_workflows');

        $checkWorkflowActionExist = WorkflowAction::query()->get($id);
        if (! $checkWorkflowActionExist) {
            return $this->response(false);
        }

        $data = [
            'to' => $to,
            'title' => $title,
            'message' => $message,
            'status' => $status,
            'run_workflows' => $run_workflows === 1
        ];

        WorkflowAction::where('id', $id)->update([
            'data' => json_encode($data),
            'is_active' => $is_active
        ]);

        return $this->response(true);
    }
}

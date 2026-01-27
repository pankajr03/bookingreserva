<?php

namespace BookneticApp\Providers\Common;

use BookneticApp\Backend\Settings\Helpers\LocalizationService;
use BookneticApp\Providers\Core\Bootstrap;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\FSCode\Clients\FSCodeAPIClient;
use BookneticApp\Providers\FSCode\Clients\RequestDTOs\ActivateRequestDTO;
use BookneticApp\Providers\FSCode\Services\FSCodeApiService;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use Exception;

class PluginService
{
    private const LOCK_MIGRATION_OPTION_NAME = 'migration_is_running';
    private FSCodeApiService $apiService;
    private FSCodeAPIClient $apiClient;

    public function __construct(FSCodeAPIClient $apiClient, FSCodeApiService $apiService)
    {
        $this->apiClient = $apiClient;
        $this->apiService = $apiService;
    }

    public function activate(string $licenseCode, string $email, int $subscribedToNewsletter, string $foundFrom): void
    {
        $dto = new ActivateRequestDTO();
        $dto->licenseCode = $licenseCode;
        $dto->siteUrl = site_url();
        $dto->pluginVersion = Helper::isSaaSVersion() ? \BookneticSaaS\Providers\Helpers\Helper::getVersion() : Helper::getVersion();
        $dto->email = $email;
        $dto->receiveEmails = $subscribedToNewsletter;
        $dto->statisticData = $foundFrom;

        $result = $this->apiService->activate($dto);

        Helper::setOption('purchase_code', $result['license_code'], false);

        ignore_user_abort(true);
        set_time_limit(0);

        $this->fetchAndRunMigrationData('booknetic');
        if (Helper::isSaaSVersion()) {
            $this->fetchAndRunMigrationData('booknetic-saas');
        }

        register_uninstall_hook(dirname(__DIR__, 3) . '/init.php', [ Helper::class, 'uninstallPlugin' ]);

        LocalizationService::restoreLocalizations();
        Helper::deleteOption('addons_updates_cache', false);
    }

    public function reactivate(string $licenseCode): void
    {
        $dto = new ActivateRequestDTO();
        $dto->licenseCode = $licenseCode;
        $dto->siteUrl = site_url();
        $dto->pluginVersion = Helper::getVersion();

        $this->apiService->activate($dto);

        Helper::setOption('plugin_disabled', '0', false);
        Helper::setOption('plugin_alert', '', false);
        Helper::setOption('purchase_code', $licenseCode, false);
    }

    public function fetchAndRunMigrationData(string $product): void
    {
        if (! Helper::isPluginActivated()) {
            return;
        }

        if ($product === 'booknetic') {
            $currentVersion = Helper::getVersion();
            $lastUpdatedVersion = Helper::getOption('plugin_version', '0.0.0', false);
        } else {
            $currentVersion = \BookneticSaaS\Providers\Helpers\Helper::getVersion();
            $lastUpdatedVersion = Helper::getOption('saas_plugin_version', '0.0.0', false);
        }

        if ($lastUpdatedVersion === $currentVersion || $this->isMigrationServiceAlreadyRunning()) {
            return;
        }

        $requestData = [
            'old_version' => $lastUpdatedVersion
        ];

        try {
            $response = $this->apiClient->requestNew($product.'/product/get_migration_data', 'POST', $requestData);

            $result = $response->getData();

            if (isset($result['data']['migrations']) && is_array($result['data']['migrations'])) {
                $this->runMigrations($result['data']['migrations']);
            }
        } catch (Exception $e) {
        }

        if ($product === 'booknetic') {
            Helper::setOption('plugin_version', $currentVersion, false);
        } else {
            Helper::setOption('saas_plugin_version', $currentVersion, false);
        }

        $this->unlockMigrationService();
    }

    private function runMigrations(array $migrationsArr): void
    {
        set_time_limit(0);

        try {
            $timezone = Date::format('P');
            DB::DB()->query("set time_zone = '$timezone';");
        } catch (\Exception $e) {
        }

        $migrationFiles = [];

        foreach ($migrationsArr as $migration) {
            if ($migration['type'] === 'sql') {
                $sql = str_replace(
                    [ '{tableprefix}', '{tableprefixbase}' ],
                    [ DB::DB()->base_prefix . DB::PLUGIN_DB_PREFIX, DB::DB()->base_prefix ],
                    base64_decode($migration['data'])
                );
                $sqlArr = preg_split('/;\n|;\r/', $sql, -1, PREG_SPLIT_NO_EMPTY);

                foreach ($sqlArr as $sqlQueryOne) {
                    $sqlQueryOne = trim($sqlQueryOne);

                    if (empty($sqlQueryOne)) {
                        continue;
                    }

                    try {
                        DB::DB()->query($sqlQueryOne);
                    } catch (\Exception $e) {
                    }
                }
            } elseif ($migration['type'] === 'script') {
                $migrationFile  = base64_decode($migration['data']);
                $fileName       = __DIR__ . DIRECTORY_SEPARATOR . 'bkntc_migration_' . time() . '_' . count($migrationFiles) . '.php';

                $migrationFiles[] = $fileName;

                file_put_contents($fileName, $migrationFile);

                include $fileName;
            }
        }

        foreach ($migrationFiles as $migrationFile) {
            @unlink($migrationFile);
        }
    }

    public function fetchAndRunAddonsMigrationData(): void
    {
        $product = Helper::isSaaSVersion() ? 'booknetic-saas' : 'booknetic';
        foreach (Bootstrap::$addons as $addon) {
            $addonSlug = $addon::getAddonSlug();
            $currentVersion = $addon::getVersion();
            $versionOnDb = Helper::getOption("addon_{$addonSlug}_version", '0.0.0', false);

            if (version_compare($currentVersion, $versionOnDb, '>')) {
                set_time_limit(0);

                try {
                    $response = $this->apiClient->requestNew($product.'/addons/get_migrations/'.$addonSlug, 'POST', [
                        'from' => $versionOnDb,
                        'to' => $currentVersion
                    ]);

                    $migrations = $response->getData();

                    if (isset($migrations['data']['migrations']) && is_array($migrations['data']['migrations'])) {
                        $this->runMigrations($migrations['data']['migrations']);
                    }

                    Helper::setOption("addon_{$addonSlug}_version", $currentVersion, false);
                } catch (\Exception $e) {
                }
            }
        }
    }

    private function isMigrationServiceAlreadyRunning(): bool
    {
        $optionName = DB::PLUGIN_DB_PREFIX . self::LOCK_MIGRATION_OPTION_NAME;

        $oldValue = DB::DB()->show_errors(false);
        $query = DB::DB()->prepare("INSERT INTO `" . DB::DB()->base_prefix . "options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, 'no') ", [ $optionName, (string)Date::epoch() ]);
        DB::DB()->query($query);
        DB::DB()->show_errors($oldValue);

        $isAlreadyRunning = DB::DB()->rows_affected === 0;

        /** yoxlayag belke hansisa sebeben 1 saatdan choxdur ilishib qalib? */
        if ($isAlreadyRunning) {
            $value = DB::DB()->get_row(DB::DB()->prepare("SELECT * FROM `" . DB::DB()->base_prefix . "options` WHERE `option_name`=%s", [ $optionName ]), ARRAY_A);

            if (isset($value['option_value']) && $value['option_value'] > 0 && (Date::epoch() - $value['option_value']) > 60 * 60) {
                $this->unlockMigrationService();
            }
        }

        return $isAlreadyRunning;
    }

    private function unlockMigrationService(): void
    {
        $optionName = DB::PLUGIN_DB_PREFIX . self::LOCK_MIGRATION_OPTION_NAME;
        DB::DB()->query(DB::DB()->prepare("DELETE FROM `" . DB::DB()->base_prefix . "options` WHERE `option_name`=%s", [ $optionName ]));
    }
}

<?php

namespace Scommerce\Core\Model;

use Scommerce\Core\Model\Config;
use Scommerce\Core\Helper\Data;
use Scommerce\Core\Model\Api\Request;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Scommerce\Core\Model\LicenseUpdater;

class SendVerify
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Data
     */
    protected $coreHelper;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var LicenseUpdater
     */
    protected $licenseUpdater;

    public function __construct(
        Config $config,
        Data $coreHelper,
        Request $request,
        Json $json,
        StoreManagerInterface $storeManager,
        LicenseUpdater $licenseUpdater
    ) {
        $this->config = $config;
        $this->coreHelper = $coreHelper;
        $this->request = $request;
        $this->json = $json;
        $this->storeManager = $storeManager;
        $this->licenseUpdater = $licenseUpdater;
    }

    public function sendVerify($moduleName, $websiteId)
    {
        $responseData = [
            'success' => false,
            'message' => "Something went wrong during request to API",
        ];
        try {
            $allModulesInWebsite = $this->json->unserialize($this->config->getModules($websiteId));
            $moduleToVerify = $allModulesInWebsite[$moduleName];
            $website = $this->storeManager->getWebsite($websiteId);
            $store = $website->getDefaultStore();
            $signature = gmdate('Ymd');
            $sku = strtolower(str_replace('Scommerce_', '', $moduleName));
            $version = $moduleToVerify['installed_version'];
            $host = $store->getBaseUrl();
            $licenseKey = $this->config->getLicenseKey($moduleToVerify['license_key_path'], $websiteId);
            $storeEmail = $this->config->getStoreEmail($websiteId);
            $requestData = [
                "signature" => $signature,
                "sku" => $sku,
                "version" => $version,
                "host" => $host,
                "licenseKey" => $licenseKey,
                "email" => $storeEmail,
            ];
            $apiUrl = $this->config->getApiEndpoint();
            $headers = ['Content-Type: application/json'];
            $result = $this->request->sendRequest($apiUrl, 'POST', $requestData, $headers);
            if (is_string($result)) {
                throw new \Exception($result);
            }
            if (isset($result['response']) && $result['response']['success']) {
                $responseData = $result['response'];
                $this->licenseUpdater->createOrUpdateLicense($websiteId, $moduleName, $version);
                $status = $result['response']['message'];
            } else {
                $responseData['success'] = false;
                $responseData['message'] = $result['response']['message'];
                $this->licenseUpdater->removeLicense($websiteId, $this->coreHelper->getSKU($moduleName));
                $status = $result['response']['message'];
            }
            $this->updateModulesData($allModulesInWebsite, $moduleName, $websiteId, $status, $result);
        } catch (\Exception $e) {
            $responseData['trace'] = [
                'apiUrl' => $apiUrl ?? '',
                'result' => $result ?? '',
                'request' => $requestData ?? '',
                'module' => $moduleName,
                'website' => $websiteId,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ];
        }

        return $responseData;
    }

    private function updateModulesData($allModules, $moduleName, $websiteId, $status, $result)
    {
        $allModules[$moduleName]['status'] = $status;
        if (isset($result['response'])) {
            $allModules[$moduleName]['latest_extension_version'] = isset($result['response']['latest_extension_version']) ? $result['response']['latest_extension_version'] : "No information available now";
            $allModules[$moduleName]['latest_available_version'] = isset($result['response']['latest_available_version']) ? $result['response']['latest_available_version'] : "No information available now";
        }
        $this->config->setModules($this->json->serialize($allModules), $websiteId);
    }
}

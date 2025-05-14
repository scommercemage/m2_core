<?php

namespace Scommerce\Core\Model;

use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Scommerce\Core\Model\Config;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;

class InstalledModules
{
    const VENDOR = 'Scommerce_';

    protected $addedModules = false;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ModuleListInterface
     */
    protected $moduleList;

    /**
     * @var ComponentRegistrar
     */
    protected $componentRegistrar;

    /**
     * @var ReadFactory
     */
    protected $readFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    protected $websiteId;

    public function __construct(
        ModuleListInterface $moduleList,
        ComponentRegistrar $componentRegistrar,
        ReadFactory $readFactory,
        Json $json,
        RequestInterface $request,
        Config $config,
        StoreManagerInterface $storeManager
    ) {
        $this->moduleList = $moduleList;
        $this->componentRegistrar = $componentRegistrar;
        $this->readFactory = $readFactory;
        $this->json = $json;
        $this->config = $config;
        $this->request = $request;
        $this->storeManager = $storeManager;
    }

    public function getModuleList($websiteId = null)
    {
        $preparedModules = $this->getPreparedModules($websiteId);

        $jsonData = $this->json->serialize($preparedModules);
        $this->config->setModules($jsonData, $this->websiteId);

        return $preparedModules;
    }

    public function getPreparedModules($websiteId)
    {
        $scommerceModules = $this->getScommerceModules();
        if (is_null($websiteId)) {
            $defaultWebsiteId = $this->storeManager->getDefaultStoreView()->getWebsiteId();
            $this->websiteId = is_null($this->request->getParam(Config::WEBSITE))
                ? $defaultWebsiteId
                : $this->request->getParam(Config::WEBSITE);
        } else {
            $this->websiteId = $websiteId;
        }
        $modulesFromConfig = $this->getModulesFromConfig($websiteId);

        $finalModules = $this->checkAndCompareModules($modulesFromConfig, $scommerceModules);

        return $finalModules;
    }

    public function getModulesFromConfig($websiteId = null)
    {
        $websiteId = is_null($websiteId) ? $this->websiteId : $websiteId;
        return $this->json->unserialize($this->config->getModules($websiteId));
    }

    public function getWebsiteId()
    {
        if (is_null($this->websiteId)) {
            $this->websiteId = $this->request->getParam(Config::WEBSITE) ?? 0;
        }

        return $this->websiteId;
    }

    private function checkAndCompareModules($modulesFromConfig, $scommerceModules)
    {
        foreach ($scommerceModules as $moduleName => $moduleConfig) {

            if (!isset($modulesFromConfig[$moduleName])) {
                $version = $moduleConfig['setup_version'];
                $licenseKeyPath = $moduleConfig['license_key_path'];
                $modulesFromConfig[$moduleName] = [
                    'name' => $moduleConfig['name'],
                    'installed_version' => $version,
                    'latest_version' => "",
                    'license_key_path' => $licenseKeyPath
                ];
                $this->addedModules = true;
            }
        }
        foreach ($modulesFromConfig as $moduleName => $moduleConfig) {
            if (!isset($scommerceModules[$moduleName])) {
                unset($modulesFromConfig[$moduleName]);
                $this->addedModules = true;
            }
        }

        return $modulesFromConfig;
    }

    private function getScommerceModules()
    {
        $allModules = $this->moduleList->getAll();
        $scommerceModules = [];
        foreach ($allModules as $moduleName => $moduleData) {
            if (strpos($moduleName, self::VENDOR) !== false) {
                $licenseKeyPath = $this->getLicenseKeyPath($moduleName);
                if ($licenseKeyPath) {
                    $moduleData['license_key_path'] = $licenseKeyPath;
                    $sku = $this->config->getSkuByModuleName($moduleName);
                    $scommerceModules[$sku] = $moduleData;
                }
            }
        }
        return $scommerceModules;
    }

    private function getLicenseKeyPath($moduleName)
    {
        $modulePath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);

        $systemXmlPath = $modulePath . '/etc/adminhtml/system.xml';
        if (!file_exists($systemXmlPath)) {
            return false;
        }

        $xml = simplexml_load_file($systemXmlPath);
        if (!$xml) {
            return false;
        }

        $licenseField = $xml->xpath('//field[@id="license_key"]');
        if ($licenseField) {
            foreach ($licenseField as $field) {
                $group = $field->xpath('ancestor::group')[0];
                $section = $group->xpath('ancestor::section')[0];
                if ($group && $section) {
                    $groupId = (string) $group['id'];
                    $sectionId = (string) $section['id'];
                    return $sectionId . '/' . $groupId . '/license_key';
                }
            }
        }

        return false;
    }
}

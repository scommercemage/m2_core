<?php
/**
 * SCommerce Mage Core Data Helper
 *
 * Copyright © 2019 SCommerce Mage. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Scommerce\Core\Helper;
use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\Registry;
use \Magento\Framework\App\Helper\Context;
use \Magento\Store\Model\StoreManagerInterface;
use Scommerce\Core\Model\Config;

class Data extends AbstractHelper
{
    private $checkedLicenses = [];
    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        Registry $registry,
        StoreManagerInterface $storeManager,
        Config $config
    ) {
        parent::__construct($context);
        $this->_registry = $registry;
        $this->_storeManager = $storeManager;
        $this->config = $config;;
    }

	/**
     * returns whether license key is valid or not
     * @param $licenseKey string
     * @param $sku string
     * @return bool
     */
    public function isLicenseValid($licenseKey,$sku){$websiteId = $this->_storeManager->getStore()->getWebsiteId();if (!$websiteId) {$websiteId = $this->_storeManager->getDefaultStoreView()->getWebsiteId();}if (isset($this->checkedLicenses[$websiteId][$sku])) {return $this->checkedLicenses[$websiteId][$sku];}$registeredVersion = $this->config->getRegisteredVersion($sku,$websiteId);$installedVersion = $this->config->getInstalledVersion($sku,$websiteId);$this->checkedLicenses[$websiteId][$sku] = $registeredVersion === $installedVersion;return $this->checkedLicenses[$websiteId][$sku];}

	/**
     * returns real sku for license key
     * @param $sku string
     * @return string
     */
	public function getSKU($sku) {if (strpos($sku,'_')!==false) {$sku=strtolower(substr($sku,0,strpos($sku,'_')));} return $sku;}

	/**
     * returns real sku for license key
     * @param $website string
     * @return string
     */
	public function getWebsite($website) {$website = strtolower($website);$website=str_replace('https:','',str_replace('/','',str_replace('http:','',str_replace('www.', '', $website))));return $website;}

	/**
     * returns if the give URL is valid or not
     * @param $website string
     * @return bool
     */
	public function isUrlValid($website)
	{
		$bits = explode('/', $website);
		if ($bits[0]=='http:' || $bits[0]=='https:'){
			$website= $bits[2];
		} else {
			$website= $bits[0];
		}
		unset($bits);

		$bits = explode('.', $website);
		$idz=0;
		while (isset($bits[$idz])){
			$idz+=1;
		}
		$idz-=3;
		$idy=0;
		while ($idy<$idz){
			unset($bits[$idy]);
			$idy+=1;
		}
		$part=array();
		foreach ($bits AS $bit){
			$part[]=$bit;
		}
		unset($bit);
		unset($bits);
		unset($website);

		if (strlen($part[1])>3){
			unset($part[0]);
		}

		foreach($part AS $bit){
			$website.=$bit.'.';
		}
		unset($bit);
		return preg_replace('/(.*)\./','$1',$website);
	}

}

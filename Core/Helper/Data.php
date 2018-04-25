<?php
/**
 * Scommerce Core Data Helper
 *
 * Copyright © 2018 Scommerce Mage. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Scommerce\Core\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($context);
        $this->_registry = $registry;
    }
	
	/**
     * returns whether license key is valid or not
     *
     * @return bool
     */
    public function isLicenseValid($licensekey,$sku){$website = $this->getWebsite($_SERVER['HTTP_HOST']);$sku=$this->getSKU($sku);return password_verify($website.'_'.$sku, $licensekey);}
	
	/**
     * returns license key for website and sku
     *
     * @return bool
     */
	public function generateKey($website,$sku){$website = $this->getWebsite($website);$sku=$this->getSKU($sku);$original_license = password_hash($website.'_'.$sku, PASSWORD_BCRYPT);return $original_license;}
	
	/**
     * returns real sku for license key
     *
     * @return string
     */
	public function getSKU($sku) {if (strpos($sku,'_')!==false) {$sku=strtolower(substr($sku,0,strpos($sku,'_')));} return $sku;}
	
	/**
     * returns real sku for license key
     *
     * @return string
     */
	public function getWebsite($website) {$website = strtolower($website);$website=str_replace('https:','',str_replace('/','',str_replace('http:','',str_replace('www.', '', $website))));return $website;}
	
	/**
     * returns if the give URL is valid or not
     *
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
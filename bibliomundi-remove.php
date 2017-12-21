<?php
/**
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    Carlos Magno <cmagnosoares@gmail.com>
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*/

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');
require_once('bibliomundi.php');

$cookie = new Cookie('psAdmin', '', (int)Configuration::get('PS_COOKIE_LIFETIME_BO'));
$employee = new Employee((int)$cookie->id_employee);

if (!(Validate::isLoadedObject($employee) && $employee->checkPassword((int)$cookie->id_employee, $cookie->passwd)
    && (!isset($cookie->remote_addr) || $cookie->remote_addr == ip2long(Tools::getRemoteAddr()) || !Configuration::get('PS_COOKIE_CHECKIP')))) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

//if (!Tools::getIsset(Tools::getValue('action'))) {
//    $bbm = new Bibliomundi();//Instancia o Módulo
//    $bbm->clientID = Configuration::get('BBM_OPTION_CLIENT_ID');
//    $bbm->clientSecret = Configuration::get('BBM_OPTION_CLIENT_SECRET');
//    $bbm->operation = Configuration::get('BBM_OPTION_OPERATION');
//    $bbm->environment = Configuration::get('BBM_OPTION_ENVIRONMENT');
//
//    if (Tools::getValue('action') == 'proccess') {
//        $bbm->proccess();
//    }
//
//    if (Tools::getValue('action') == 'valid') {
//        $bbm->ajaxValid();
//    }
//}



//$categories = Db::getInstance()->executeS('SELECT `id_category` FROM `' . _DB_PREFIX_ . 'bbm_category`');
$products   = Db::getInstance()->executeS('SELECT `id_product`  FROM `' . _DB_PREFIX_ . 'bbm_product`');//Products are responsible for deleting Tags

//$category = new Category();
$product  = new Product();
$feature  = new Feature();
if (count($products)) {
    $product->deleteSelection(array_map(function ($product) {
        return $product['id_product'];
    }, $products));
}
$featureIDPublisherName = Configuration::get('BBM_FEATURE_ID_PUBLISHER_NAME');
$featureIDIdiom = Configuration::get('BBM_FEATURE_ID_IDIOM');
$featureIDPagesNumber = Configuration::get('BBM_FEATURE_ID_PAGES_NUMBER');
$featureIDProtectionType = Configuration::get('BBM_FEATURE_ID_PROTECTION_TYPE');
$feature->deleteSelection(
    array(
        $featureIDPublisherName,
        $featureIDIdiom,
        $featureIDPagesNumber,
        $featureIDProtectionType,
    )
);

$return = array(
    'error' => false,
    'msg'   => 'All products were deleted successfully'
);
echo Tools::jsonEncode($return);

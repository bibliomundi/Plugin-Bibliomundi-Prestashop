<?php
/**
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This file is licenced under the Software License Agreement.
* With the purchase or the installation of the software in your application
* you accept the licence agreement.
*
* You must not modify, adapt or create derivative works of this source code
*
*  @author    Carlos Magno <cmagnosoares@gmail.com>
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*/

class MYProduct extends ProductCore
{
    public function __construct($id_product = null, $full = false, $id_lang = null, $id_shop = null, Context $context = null)
    {
        parent::__construct($id_product, $full, $id_lang, $id_shop, $context);
    }      

    public static function getIDByIDBBM($idBBM)
    {
        $sql = "SELECT `id_product` FROM `" . _DB_PREFIX_ . "bbm_product` WHERE `bbm_id_product` = '" . pSQL($idBBM) . "'";
    
        return Db::getInstance()->getValue($sql);
    }

    public static function getIDBBMByID($idProduct)
    {
        $sql = "SELECT `bbm_id_product` FROM `" . _DB_PREFIX_ . "bbm_product` WHERE `id_product` = '" . pSQL((int)$idProduct) . "'";
    
        return Db::getInstance()->getValue($sql);
    }

    public static function insertBBMProduct($id_product, $bbm_id_product, $iso_code)
    {
        $sql = "INSERT INTO `" . _DB_PREFIX_ . "bbm_product` (id_product, bbm_id_product, iso_code) VALUES (" . pSQL((int)$id_product) . ", '" . pSQL($bbm_id_product) . "', '" . pSQL($iso_code) . "')";

        return Db::getInstance()->Execute($sql);
    }

    public static function getIsoCodeByIDBBM($idBBM)
    {
        $sql = "SELECT `iso_code` FROM `" . _DB_PREFIX_ . "bbm_product` WHERE `bbm_id_product` = '" . pSQL($idBBM) . "'";
    
        return Db::getInstance()->getValue($sql);
    }
}

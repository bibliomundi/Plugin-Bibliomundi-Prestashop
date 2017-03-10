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

class MYCategory extends CategoryCore
{
    public function __construct($id_category = null, $id_lang = null, $id_shop = null)
    {        
        parent::__construct($id_category, $id_lang, $id_shop);	
    }      

    public static function getIDByIDBBM($idBBM)
    {
        $sql = "SELECT `id_category` FROM `" . _DB_PREFIX_ . "bbm_category` WHERE `bbm_id_category` = '" . pSQL($idBBM) . "'";
    
        return Db::getInstance()->getValue($sql);
    }

    public static function getIDBBMByID($idCategory)
    {
        $sql = "SELECT `bbm_id_category` FROM `" . _DB_PREFIX_ . "bbm_category` WHERE `id_category` = '" . pSQL((int)$idCategory) . "'";
    
        return Db::getInstance()->getValue($sql);
    }

    public static function insertBBMCategory($id_category, $bbm_id_category)
    {
        $sql = "INSERT INTO `" . _DB_PREFIX_ . "bbm_category` (id_category, bbm_id_category) VALUES (" . pSQL((int)$id_category) . ", '" . pSQL($bbm_id_category) . "')";

        return Db::getInstance()->Execute($sql);
    }
}

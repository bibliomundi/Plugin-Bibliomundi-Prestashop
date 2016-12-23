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
	/*
    * module: bibliomundi
    * date: 2016-01-08 15:56:32
    * version: 1.0.0
    */
    /*
    * module: bibliomundi
    * date: 2016-01-08 16:07:41
    * version: 1.0.0
    */
	/*
    * module: bibliomundi
    * date: 2016-01-08 15:56:32
    * version: 1.0.0
    */
    /*
    * module: bibliomundi
    * date: 2016-01-08 16:07:41
    * version: 1.0.0
    */
	/*
    * module: bibliomundi
    * date: 2016-01-08 15:56:32
    * version: 1.0.0
    */
    /*
    * module: bibliomundi
    * date: 2016-01-08 16:07:41
    * version: 1.0.0
    */
    /*
    * module: bibliomundi
    * date: 2016-01-08 16:07:41
    * version: 1.0.0
    */
	/*
    * module: bibliomundi
    * date: 2016-01-08 15:56:32
    * version: 1.0.0
    */
    /*
    * module: bibliomundi
    * date: 2016-01-08 16:07:41
    * version: 1.0.0
    */
	/*
    * module: bibliomundi
    * date: 2016-01-08 15:56:32
    * version: 1.0.0
    */
    /*
    * module: bibliomundi
    * date: 2016-01-08 16:07:41
    * version: 1.0.0
    */
	/*
    * module: bibliomundi
    * date: 2016-01-08 15:56:32
    * version: 1.0.0
    */
    /*
    * module: bibliomundi
    * date: 2016-01-08 16:07:41
    * version: 1.0.0
    */
    /*
    * module: bibliomundi
    * date: 2016-01-08 16:07:41
    * version: 1.0.0
    */
	/*
    * module: bibliomundi
    * date: 2016-01-08 15:56:32
    * version: 1.0.0
    */
    /*
    * module: bibliomundi
    * date: 2016-01-08 16:07:41
    * version: 1.0.0
    */
    /*
    * module: bibliomundi
    * date: 2016-01-13 12:39:40
    * version: 1.0.0
    */
    public $bbm_id_category;
	/*
    * module: bibliomundi
    * date: 2016-01-08 15:56:32
    * version: 1.0.0
    */
    /*
    * module: bibliomundi
    * date: 2016-01-08 16:07:41
    * version: 1.0.0
    */
    /*
    * module: bibliomundi
    * date: 2016-01-13 12:39:40
    * version: 1.0.0
    */
    public $is_bbm;
	/*
    * module: bibliomundi
    * date: 2016-01-08 15:56:32
    * version: 1.0.0
    */
    /*
    * module: bibliomundi
    * date: 2016-01-08 16:07:41
    * version: 1.0.0
    */
    /*
    * module: bibliomundi
    * date: 2016-01-13 12:39:40
    * version: 1.0.0
    */
    public function __construct($id_category = null, $id_lang = null, $id_shop = null)
    {
        self::$definition['fields']['bbm_id_category'] = array('type' => self::TYPE_STRING);
        self::$definition['fields']['is_bbm'] = array('type' => self::TYPE_BOOL);
        
        parent::__construct($id_category, $id_lang, $id_shop);	
    }      
    /*
    * module: bibliomundi
    * date: 2016-01-08 16:07:41
    * version: 1.0.0
    */
    /*
    * module: bibliomundi
    * date: 2016-01-13 12:39:40
    * version: 1.0.0
    */
    public static function getIDByIDBBM($idBBM)
    {
        $sql = "SELECT `id_category` FROM `" . _DB_PREFIX_ . "category` WHERE `bbm_id_category` = '" . pSQL((int)$idBBM) . "' AND `is_bbm` IS NOT NULL";
    
        return Db::getInstance()->getValue($sql);
    }

    public static function getIDBBMByID($idCategory)
    {
        $sql = "SELECT `bbm_id_category` FROM `" . _DB_PREFIX_ . "product` WHERE `id_category` = '" . pSQL((int)$idCategory) . "' AND `is_bbm` IS NOT NULL";
    
        return Db::getInstance()->getValue($sql);
    }
}

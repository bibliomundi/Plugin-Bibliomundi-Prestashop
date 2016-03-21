<?php 
Class MYProduct extends ProductCore
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
    public $bbm_id_product;
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
    public function __construct($id_product = null, $full = false, $id_lang = null, $id_shop = null, Context $context = null)
    {
        self::$definition['fields']['bbm_id_product'] = array('type' => self::TYPE_STRING);
        self::$definition['fields']['is_bbm'] = array('type' => self::TYPE_BOOL);
        
        parent::__construct($id_product, $full, $id_lang, $id_shop, $context);
    }      
    /*
    * module: bibliomundi
    * date: 2016-01-13 12:39:40
    * version: 1.0.0
    */
    public static function getIDByIDBBM($idBBM)
    {
        $sql = "SELECT id_product FROM " . _DB_PREFIX_ . "product WHERE bbm_id_product = '{$idBBM}' AND is_bbm IS NOT NULL";
        
        return Db::getInstance()->getValue($sql);
    }

    public static function getIDBBMByID($idProduct)
    {
        $sql = "SELECT bbm_id_product FROM " . _DB_PREFIX_ . "product WHERE id_product = {$idProduct} AND is_bbm IS NOT NULL";
        
        return Db::getInstance()->getValue($sql);
    }
}

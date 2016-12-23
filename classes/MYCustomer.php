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

class MYCustomer extends CustomerCore
{

    //Gambiarra para trazer o country_iso e o endereço escolhido pelo usuario
	public function getMYAddresses($id_lang, $id_address)
    {
        $share_order = (bool)Context::getContext()->shop->getGroup()->share_order;
        
        $sql = 'SELECT DISTINCT a.*, cl.`name` AS country, c.`iso_code` as country_iso, s.name AS state, s.iso_code AS state_iso
				FROM `'._DB_PREFIX_.'address` a
				LEFT JOIN `'._DB_PREFIX_.'country` c ON (a.`id_country` = c.`id_country`)
				LEFT JOIN `'._DB_PREFIX_.'country_lang` cl ON (c.`id_country` = cl.`id_country`)
				LEFT JOIN `'._DB_PREFIX_.'state` s ON (s.`id_state` = a.`id_state`)
				'.($share_order ? '' : Shop::addSqlAssociation('country', 'c')).'
				WHERE `id_lang` = '.(int)$id_lang.' AND `id_customer` = '.(int)$this->id.' AND a.`deleted` = 0 AND a.`id_address` = ' . (int) $id_address;

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        
        return $result;
    }
}
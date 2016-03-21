<?php

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
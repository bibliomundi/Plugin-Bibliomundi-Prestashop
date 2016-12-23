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

class Validate extends ValidateCore
{
    /**
     * Check for product or category name validity
     *
     * @param string $name Product or category name to validate
     * @return boolean Validity is ok or not
     */
    public static function isCatalogName($name)
    {
        return 1; //Permitir a inserção de ebooks com caracteres especiais como #<>;
    }
}
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

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');
require('bibliomundi.php');


$bbm = new Bibliomundi();//Instancia o Módulo
$bbm->operation = 2;//updates.

try
{
	if (!Module::isInstalled('bibliomundi'))
		throw new Exception("Module bibliomundi not installed");	
		
	$bbm->proccess();
}
catch(Exception $e)
{
	$bbm->{'msgLog'} = $e->getMessage();
}

$bbm->writeLog();

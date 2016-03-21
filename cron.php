<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');
require('bibliomundi.php');


$bbm = new Bibliomundi();//Instancia o Módulo
$bbm->operation = 2;//updates.

try
{
	if (!Module::isInstalled('bibliomundi'))
		throw new Exception("Módulo bibliomundi não instalado");	
		
	$bbm->proccess();
}
catch(Exception $e)
{
	$bbm->{'msgLog'} = $e->getMessage();
}
finally
{
	$bbm->writeLog();
}

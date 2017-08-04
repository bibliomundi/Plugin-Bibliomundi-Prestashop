<?php
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');
require('bibliomundi.php');
require_once dirname(__FILE__) . '/classes/MYImage.php';
require_once dirname(__FILE__).'/../../classes/module/Module.php';
class DownloadImage extends Module
{
};

$argv = $_SERVER['argv'];
$productId = $argv[1];
$bbmProductTitle = $argv[2];
$bbmProductUr1File = $argv[3];
$totalProduct = $argv[4];
$image = new MYImage();
$downloadImage = new DownloadImage();
$image->id_product = $productId;
$image->position = MYImage::getHighestPosition($productId) + 1;
$image->cover = true;
$image->legend = $bbmProductTitle;

if (($image->validateFields(false, true)) === true && ($image->validateFieldsLang(false, true)) === true && $image->add()) {
    $image_url = 'http://'.$bbmProductUr1File;
    if (empty(getimagesize($image_url))) {
        $image_url = 'https://avatars0.githubusercontent.com/u/12715450?v=3&s=400';
    }

    if (!$image->copy($productId, $image_url, 'products', true, $image->id)) {
        $image->delete();
    }
    // Get current result
    $result = Tools::file_get_contents(dirname(__FILE__).'/log/import.lock');
    $result = json_decode($result, true);
    $result['current'] = !isset($result['current']) ? 1 : $result['current'] + 1;
    $result['current'] = ($result['current'] >= $totalProduct) ? $totalProduct : $result['current'];
    $lock = fopen(dirname(__FILE__).'/log/import.lock', 'a');
    ftruncate($lock, 0);
    if (isset($result['current']) && $result['current'] == $totalProduct) {
        $result['status'] = 'complete';
        $result['content'] = $downloadImage->l('Successful operation!');
    }
    fwrite($lock, Tools::jsonEncode($result));
    fclose($lock);
}

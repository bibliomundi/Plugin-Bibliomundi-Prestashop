<?php
/**
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    Carlos Magno <cmagnosoares@gmail.com>
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Bibliomundi extends Module
{
    public $clientID;
    public $clientSecret;
    public $operation;
    public $environment;
    public $operationAlias = array(1 => 'complete', 2 => 'updates');
    public $environmentAlias = array(1 => 'sandbox', 2 => 'production');
    public $addEbooksCat = array(1 => 'yes', 2 => 'no');

    /*
     * The features will be the personalised fields of our modules. Do not attempt workarounds, please!
     */
    
    public $featureIDISBN;
    public $featureIDPublisherName;
    public $featureIDFormatType;
    public $featureIDEditionNumber;
    public $featureIDIdiom;
    public $featureIDPagesNumber;
    public $featureIDProtectionType;
    public $featureIDAgeRating;
    public $featureIDCollectionTitle;
    public $featureIDAutor;//Authors are also inserted as features
    public $featureIDIlustrador;//Authors are also inserted as features
    public $product_iso_code;

    public $max_sandbox_products = 8;

   /*
    *
    * The Author will be inserted as Feature by default, but we will still allow
    * The user to choose between inserting authors as Categories or Tags
    *
    */

    public $categoryIDAutor;//Category ID in which Author will be Parent of other inserted Authors
    
    /**
     * Store logging messages
     * @var [type]
     */
    public $msgLog;

    public function __construct()
    {
        $this->name = 'bibliomundi';
        $this->version = '1.0.0';
        $this->author = 'Bibliomundi';
        $this->tab = 'others';
        $this->displayName = $this->l('Integration with Bibliomundi ebooks');
        $this->description = $this->l('Digital book distributor.');
        $this->module_key = '343e3911dab411114fa84233c195abdf';
        $this->ps_versions_compliancy = array('min' => '1.6');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall our module?');
        $this->bootstrap = true;
        
        parent::__construct();
        $this->loadFiles();
        $this->getConfig();
        $this->registerHook('actionValidateOrder');
        $this->context->controller->addJS($this->_path.'views/js/app.js');
        $this->context->controller->addJS('/js/jquery/plugins/blockui/jquery.blockUI.js');
    }

    public function install()
    {
        if (!parent::install() ||
            !$this->createFeaturesAndOptions() ||
                !$this->createCustomFieldsToDB() ||
                    !$this->registerHook('actionPaymentConfirmation') ||
                        !$this->registerHook('displayBeforePayment') ||
                            !$this->registerHook('actionCartSave') ||
                                !$this->registerHook('actionDownloadBBMFile') ||
                                    !$this->registerHook('actionBeforeCartUpdateQty') ||
                                        !$this->registerHook('actionOrderStatusPostUpdate') ||
                                            !$this->registerHook('actionProductDelete') ||
                                                !$this->registerHook('actionCategoryDelete') ||
                                                    //!$this->registerHook('displayProductTab') ||
                                                        !$this->registerHook('actionValidateOrder')) {
            return false;
        }
        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall() || !$this->deleteFromDB()) {
            return false;
        }
        return true;
    }
    
    private function setConfig()
    {
        /* These are the only values that may be updated after insatallation */
        Configuration::updateValue('BBM_OPTION_CLIENT_ID', $this->clientID);
        Configuration::updateValue('BBM_OPTION_CLIENT_SECRET', $this->clientSecret);
        Configuration::updateValue('BBM_OPTION_OPERATION', $this->operation);
        Configuration::updateValue('BBM_OPTION_ENVIRONMENT', $this->environment);
        Configuration::updateValue('BBM_OPTION_ADD_EBOOKS_CAT', $this->addEbooksCat);
    }

    private function getConfig()
    {
        /* Complete verification even if, sometimes, the return might be NULL */

        $this->clientID                 = Configuration::get('BBM_OPTION_CLIENT_ID');
        $this->clientSecret             = Configuration::get('BBM_OPTION_CLIENT_SECRET');
        $this->operation                = Configuration::get('BBM_OPTION_OPERATION');
        $this->environment              = Configuration::get('BBM_OPTION_ENVIRONMENT');
        $this->addEbooksCat              = Configuration::get('BBM_OPTION_ADD_EBOOKS_CAT');

//        $this->featureIDISBN            = Configuration::get('BBM_FEATURE_ID_ISBN');
//        $this->featureIDPublisherName   = Configuration::get('BBM_FEATURE_ID_PUBLISHER_NAME');
//        $this->featureIDFormatType      = Configuration::get('BBM_FEATURE_ID_FORMAT_TYPE');
//        $this->featureIDEditionNumber   = Configuration::get('BBM_FEATURE_ID_EDITION_NUMBER');
//        $this->featureIDIdiom           = Configuration::get('BBM_FEATURE_ID_IDIOM');
//        $this->featureIDPagesNumber     = Configuration::get('BBM_FEATURE_ID_PAGES_NUMBER');
//        $this->featureIDProtectionType  = Configuration::get('BBM_FEATURE_ID_PROTECTION_TYPE');
//        $this->featureIDAgeRating       = Configuration::get('BBM_FEATURE_ID_AGE_RATING');
//        $this->featureIDCollectionTitle = Configuration::get('BBM_FEATURE_ID_COLLECTION_TITLE');
//        $this->featureIDAutor           = Configuration::get('BBM_FEATURE_ID_AUTOR');
//        $this->featureIDIlustrador      = Configuration::get('BBM_FEATURE_ID_ILUSTRADOR');
        $this->featureIDPublisherName   = Configuration::get('BBM_FEATURE_ID_PUBLISHER_NAME');
        $this->featureIDIdiom           = Configuration::get('BBM_FEATURE_ID_IDIOM');
        $this->featureIDPagesNumber     = Configuration::get('BBM_FEATURE_ID_PAGES_NUMBER');
        $this->featureIDProtectionType  = Configuration::get('BBM_FEATURE_ID_PROTECTION_TYPE');

        if (Configuration::get('BBM_AUTOR_INSERT_TYPE') == 2) {
            $aux = Configuration::get('BBM_CATEGORY_ID_AUTOR');

            if (!Category::categoryExists($aux)) { //In case the user has deleted the Author Category
                $category = new Category();
                $category->name[(int)Configuration::get('PS_LANG_DEFAULT')] = 'Autor';
                $category->link_rewrite[(int)Configuration::get('PS_LANG_DEFAULT')] = 'Autor';
                $category->id_parent = Category::getRootCategory()->id;//Associate to Home Category
                $category->add();

                Configuration::updateValue('BBM_CATEGORY_ID_AUTOR', $category->id);
                $aux = Configuration::get('BBM_CATEGORY_ID_AUTOR');
            }

            $this->categoryIDAutor = $aux;
        }
    }

    public function getCatalog()
    {
        $catalog = new BBM\Catalog($this->clientID, $this->clientSecret, $this->operationAlias[$this->operation]);
        $catalog->environment = $this->environmentAlias[$this->environment];
        //$catalog->verbose(true);

        try {
            $catalog->validate();
            return $catalog->get();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function ajaxValid()
    {
        $catalog = new BBM\Catalog($this->clientID, $this->clientSecret, $this->operationAlias[$this->operation]);
        $catalog->environment = $this->environmentAlias[$this->environment];
        echo Tools::jsonEncode($catalog->ajax_validate());
        die;
    }

    //Core of Module!
    public function proccess()
    {
        $result = array(
            'status' => 'in progress'
        );
        
        self::_updateImportLog($result);
        
        try {
            set_time_limit(0);//Avoids timeout, considering there will be a number of operations

            $parser = new \BBMParser\OnixParser($this->getCatalog());
            if (!$productsAvailable = $parser->getOnix()->getProductsAvailable()) {
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                    $result['status'] = 'error';
                    $result['content'] = $this->l('There are no ebooks to import!');
                    self::_updateImportLog($result);
                    die;
                } else {
                    throw new Exception("There are no ebooks to import!");
                }
            }

            $result['total'] = count($productsAvailable);
            $result['current'] = 0;

            //Only get a maximum number of products when environment is sandbox
            $stop_forearch = 0;
            $this->max_sandbox_products = 8;
            if ('sandbox' == $this->environmentAlias[$this->environment]) {
                $stop_forearch = $this->max_sandbox_products;
                $result['total'] = ($this->max_sandbox_products < count($productsAvailable)) ? $this->max_sandbox_products : count($productsAvailable);
            }
            
            $downloadingImageFlg = false;
            $ebooksCatId = $this->_getEbookCategoryId();
            //Be it Complete or Update, it will all be here!
            foreach ($productsAvailable as $key => $bbmProduct) {
                if ($stop_forearch && $key >= $stop_forearch) {
                    break;
                }
                
                if (!$downloadingImageFlg) {
                    $result['current'] = ($result['current'] >= $result['total']) ? $result['total'] : $result['current'] + 1;
                    self::_updateImportLog($result);
                }
                $this->product_iso_code = '';
                $product = new MYProduct();
                $idProductAlreadyInserted = MYProduct::getIDByIDBBM($bbmProduct->getId());//Verifies if it exists

                //Avoids duplicated entries
                if ($idProductAlreadyInserted && $bbmProduct->getOperationType() == '03') {
                    if ($downloadingImageFlg) {
                        $result['current'] = ($result['current'] >= $result['total']) ? $result['total'] : $result['current'] + 1;
                        self::_updateImportLog($result);
                    }
                    continue;
                }

                if ($idProductAlreadyInserted && $bbmProduct->getOperationType() == '04') {
                    $product = new MYProduct($idProductAlreadyInserted);
                }

                //In case the process of Updated includes the Deletion of a Product
                if ($bbmProduct->getOperationType() == '05') {
                    if ($downloadingImageFlg) {
                        $result['current'] = ($result['current'] >= $result['total']) ? $result['total'] : $result['current'] + 1;
                        self::_updateImportLog($result);
                    }
                    if ($idProductAlreadyInserted) {
                        $product = new MYProduct($idProductAlreadyInserted);
                        $product->delete();
                        continue;//Moves on to next ebook
                    } else {
                        continue;
                    }
                }

                $product->name[(int)Configuration::get('PS_LANG_DEFAULT')] = $bbmProduct->getTitle();
                $product->meta_keywords[(int)Configuration::get('PS_LANG_DEFAULT')] = $bbmProduct->getTitle();
                $product->link_rewrite[(int)Configuration::get('PS_LANG_DEFAULT')] = Tools::link_rewrite($bbmProduct->getTitle());

                $bbmPrice = array();
                foreach ($bbmProduct->getPrices() as $price) {
                    $bbmPrice[$price->getCurrency()] = $price->getAmount();
                }
                if (in_array($this->context->currency->iso_code, array_keys($bbmPrice))) {
                    $product->price = $bbmPrice[$this->context->currency->iso_code];
                    $this->product_iso_code = $this->context->currency->iso_code;
                } else {
                    $product->price = $bbmPrice['BRL'];
                    $this->product_iso_code = 'BRL';
                }

                $product->description_short[(int)Configuration::get('PS_LANG_DEFAULT')] = $bbmProduct->getSubTitle();
                $product->description[(int)Configuration::get('PS_LANG_DEFAULT')] = $bbmProduct->getSynopsis();
                $product->id_manufacturer = 0;
                $product->id_supplier = 0;
                $product->minimal_quantity = 1;
                $product->additional_shipping_cost = 0;
                $product->wholesale_price = 0;
                $product->ecotax = 0;
                $product->width = 0;
                $product->height = 0;
                $product->depth = 0;
                $product->weight = 0;
                $product->out_of_stock = 2;
                $product->active = 1;
                $product->available_for_order = 1;
                $product->show_price = 1;
                $product->online_only = 1;
                $product->is_virtual = 1;
                $product->id_tax_rules_group = 0;
                $product->indexed = 1;

                $categoriesIds = array();//Inserts firstly the categories and then associate them to the product
                $tags          = $bbmProduct->getTags();//If there are no Tags, a empty array is returned

                //Topics (Categories)
                if (count($bbmProduct->getCategories())) {
                    foreach ($bbmProduct->getCategories() as $bbmCategory) {
                        $category = new MYCategory();

                        if ($id = MYCategory::getIDByIDBBM($bbmCategory->getCode())) {
                            $category = new MYCategory($id);
                        } else { //Insert a new Category
                            if (empty($bbmCategory->getName())) {
                                continue;
                            }
                            $category->name[(int)Configuration::get('PS_LANG_DEFAULT')] = $bbmCategory->getName();
                            $category->link_rewrite[(int)Configuration::get('PS_LANG_DEFAULT')] = Tools::link_rewrite($bbmCategory->getCode());
                            // Add all imported categories to ebooks category
                            $category->id_parent = $ebooksCatId;
                            //$category->id_parent = Category::getRootCategory()->id;//Associates a Default Category, which usually is Home
                            $category->add();
                            $category->insertBBMCategory($category->id, $bbmCategory->getCode());
                        }
                        $categoriesIds[] = $category->id;
                    }
                }
                
                //According to Premisses, the Author MAY NOT CHANGE, therefore we ignore them on Update routine
                if ($bbmProduct->getOperationType() == '03') {
                    if (Configuration::get('BBM_AUTOR_INSERT_TYPE') != 3) { //3 Keeps Author as Feature
                        //Contributors
                        if (count($bbmProduct->getContributors())) {
                            foreach ($bbmProduct->getContributors() as $contributor) {
                                if ($contributor instanceof \BBMParser\Model\Author) {
                                    //Authors are inserted as Category, Tag, or simply leave them as a Feature

                                    if (Configuration::get('BBM_AUTOR_INSERT_TYPE') == 1) { //Insert as Tag
                                        $tags .= ';'.$contributor->getFullName();
                                    } else if (Configuration::get('BBM_AUTOR_INSERT_TYPE') == 2) { //Insert as Category
                                        $category = new MYCategory();

                                        if ($id = MYCategory::getIDByIDBBM($contributor->getId())) {
                                            $category = new MYCategory($id);
                                        } else { //Inserts a new Category
                                            $category->name[(int)Configuration::get('PS_LANG_DEFAULT')] = $contributor->getFullName();
                                            $category->link_rewrite[(int)Configuration::get('PS_LANG_DEFAULT')] = Tools::link_rewrite($contributor->getFullName());
                                            $category->id_parent = $this->categoryIDAutor;//Associates the Default Category as Author
                                            $category->add();
                                            $category->insertBBMCategory($category->id, $contributor->getId());
                                        }
                                        $categoriesIds[] = $category->id;
                                    }
                                }
                            }
                        }
                    }
                }

                $product->id_category_default = $categoriesIds[0];//There are no rules set here. Selection may be random

                if ($idProductAlreadyInserted && $bbmProduct->getOperationType() == '04') {
                    $product->deleteFeatures();//This is how Prestashop works internally. Deletes all Features to then recreate them.
                    $product->update();
                } else {
                    $product->add();//Add before to gain access to Insert_ID to permit operations below
                }

                $product->insertBBMProduct($product->id, $bbmProduct->getId(), $this->product_iso_code);

                //Associate all Tags, including the Author, if it´s the case, to the Product.
                //OBS: If there is already a Tag with the same name, Prestashop ignores this addition. Which is excelent for Update routine.
                $array_tags = explode(';', $tags);
                $array_tags = array_filter($array_tags);

                if (count($array_tags)) {
                    Tag::addTags((int)Configuration::get('PS_LANG_DEFAULT'), $product->id, $array_tags);
                }

                //Associates Product to all created Categories. If they exist, association is ignored!
                $product->addToCategories($categoriesIds);

                //Rules for addition and association of Custom Features

                //1- Creates a Feature and collects its ID
                //2- Creates a Value Feature by addFeaturesToDB function and collects ID
                //3- Associates Value Feature to Product and language through addFeaturesCustomToDB function

//                $idFeatureValue = $product->addFeaturesToDB($this->featureIDISBN, null, 1);
//                $product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getISBN());

                $idFeatureValue = $product->addFeaturesToDB($this->featureIDIdiom, null, 1);
                $product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getIdiom());

//                if ($bbmProduct->getCollectionTitle()) {
//                    $idFeatureValue = $product->addFeaturesToDB($this->featureIDCollectionTitle, null, 1);
//                    $product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getCollectionTitle());
//                }

//                $idFeatureValue = $product->addFeaturesToDB($this->featureIDAgeRating, null, 1);
//                $product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getAgeRatingValue());

                switch ($bbmProduct->getProtectionType()) {
                    case '01':
                        $epub_technical_protection = 'Social DRM';
                        break;
                    case '02':
                        $epub_technical_protection = 'Adobe DRM';
                        break;
                    default:
                        $epub_technical_protection = 'No DRM';
                }
                $idFeatureValue = $product->addFeaturesToDB($this->featureIDProtectionType, null, 1);
                $product->addFeaturesCustomToDB($idFeatureValue, 1, $epub_technical_protection);

                $idFeatureValue = $product->addFeaturesToDB($this->featureIDPagesNumber, null, 1);
                $product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getPageNumbers());

//                $idFeatureValue = $product->addFeaturesToDB($this->featureIDEditionNumber, null, 1);
//                $product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getEditionNumber());

//                $idFeatureValue = $product->addFeaturesToDB($this->featureIDFormatType, null, 1);
//                $product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getFormatType());
//
                $idFeatureValue = $product->addFeaturesToDB($this->featureIDPublisherName, null, 1);
                $product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getImprintName());

                //Inserts Authors separated by common commas ","
                if ($autorsName = implode(',', array_map(array('\BBMParser\Model\Contributor', 'getFullNameStatically'), $bbmProduct->getContributorsByType('Autor')))) {
                    $idFeatureValueAutor = $product->addFeaturesToDB($this->featureIDAutor, null, 1);
                    $product->addFeaturesCustomToDB($idFeatureValueAutor, 1, $autorsName);
                }

//                //Inserts Illustrators separated by common commas ","
//                if ($ilustradorsName = implode(',', array_map(array('\BBMParser\Model\Contributor', 'getFullNameStatically'), $bbmProduct->getContributorsByType('Ilustrador')))) {
//                    $idFeatureValueIlustrador = $product->addFeaturesToDB($this->featureIDIlustrador, null, 1);
//                    $product->addFeaturesCustomToDB($idFeatureValueIlustrador, 1, $ilustradorsName);
//                }

                /*
                 * Download
                 */

                if ($bbmProduct->getOperationType() == '03') {
                    $product->setDefaultAttribute(0);//reset cache_default_attribute
                    $download = new ProductDownload();
                    $download->id_product = $product->id;
                    $download->display_filename = $bbmProduct->getISBN() . '.bbm';
                    $download->nb_days_accessible = 0;
                    $download->nb_downloadable = 0;
                    $download->date_expiration = '';
                    $download->add();
                }

                //For Update routine as we may only have one Image as Product Cover
                if ($images = $product->getImages((int)Configuration::get('PS_LANG_DEFAULT'))) {
                    foreach ($images as $img) {
                        $image = new MYImage($img['id_image']);
                        $image->delete();
                    }
                }
                $downloadingImageFlg = true;
                $cmd = "php downloadImage.php " . $product->id . " \"" . $bbmProduct->getTitle() . "\" \"" . $bbmProduct->getUrlFile() . "\" " . $result['total'] ." &";
                if (Tools::substr(php_uname(), 0, 7) == "Windows") {
                    pclose(popen("start ". $cmd, "r"));
                } else {
                    pclose(popen($cmd, "r"));
                }
            }
            
            if (isset($result['current']) && $result['current'] == $result['total']) {
                $lock = fopen(dirname(__FILE__).'/log/import.lock', 'a');
                ftruncate($lock, 0);
                $result['status'] = 'complete';
                $result['content'] = $this->l('Successful operation!');
                fwrite($lock, Tools::jsonEncode($result));
                fclose($lock);
            }
        } catch (Exception $e) {
            $result['status'] = 'error';
            $result['content'] = $e->getMessage();
            throw $e;
        }
    }

    public function getContent()
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
        $output = '';

        //When the initial definition  form is issued (For now, only the addition of Authors)
        if (Tools::isSubmit('submit' . $this->name . 'autor_insert_type')) {
            if (in_array(Tools::getValue('autor_insert_type'), array('1', '2', '3'))) { //0 = No definition, 1 = Category, 2 = Tag
                if (!$this->insertAutorBy(Tools::getValue('autor_insert_type'))) {
                    $output .= $this->displayError($this->l('Internal error!'));
                } else {
                    Configuration::updateValue('BBM_AUTOR_INSERT_TYPE', Tools::getValue('autor_insert_type'));
                }
            } else {
                $output .= $this->displayError($this->l('Invalid author type!'));
            }
        } else if (Tools::isSubmit('submit' . $this->name . 'operation')) {
            if (!Tools::getValue('client_id') || !Tools::getValue('client_secret')) {
                $output .= $this->displayError($this->l('Unidentified ID Key or Secret Key!'));
            } else if (!in_array(Tools::getValue('operation'), array('1', '2')) || !in_array(Tools::getValue('environment'), array('1', '2'))) {
                $output .= $this->displayError($this->l('Invalid Operation Type or Environment!'));
            } else {
                $this->clientID       = (string)Tools::getValue('client_id');
                $this->clientSecret   = (string)Tools::getValue('client_secret');
                $this->operation      = (string)Tools::getValue('operation');
                $this->environment    = (string)Tools::getValue('environment');
                $this->addEbooksCat    = (string)Tools::getValue('add_ebooks_cat');

                try {
                    $result = array('status' => 'free');
                    
                    if (file_exists(dirname(__FILE__).'/log/import.lock')) {
                        $result = Tools::jsonDecode(Tools::file_get_contents(dirname(__FILE__).'/log/import.lock'));
                        if (!empty($result)) {
                            if ($result->status == 'in progress') {
                                if (time() - filemtime(dirname(__FILE__).'/log/import.lock') > 5) {
                                    unlink(dirname(__FILE__).'/log/import.lock');
                                }
                            }
                        }
                    }
                    
                    if ($isAjax) {
                        $post_data = array(
                            'clientID' => $this->clientID,
                            'clientSecret' => $this->clientSecret,
                            'operation' => $this->operation,
                            'environment' => $this->environment,
                            'addEbooksCat' => $this->addEbooksCat
                        );
                        $post_params = array();
                        foreach ($post_data as $key => &$val) {
                            if (is_array($val)) {
                                $val = implode(',', $val);
                            }
                            $post_params[] = $key.'='.urlencode($val);
                        }
                        
                        $post_string = implode('&', $post_params);
                        $parts = parse_url($_SERVER['HTTP_ORIGIN']);

                        $fp = fsockopen($parts['host'], isset($parts['port'])?$parts['port']:80, $errno, $errstr, 30);

                        $out = "POST /modules/bibliomundi/bibliomundi-import.php HTTP/1.1\r\n";
                        $out.= "Host: ".$_SERVER['HTTP_HOST']."\r\n";
                        $out.= "Cookie: ".$_SERVER['HTTP_COOKIE']."\r\n";
                        $out.= "User-Agent: ".$_SERVER['HTTP_USER_AGENT']."\r\n";
                        $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
                        $out.= "Content-Length: ".Tools::strlen($post_string)."\r\n";
                        $out.= "Connection: Close\r\n\r\n";
                        if (isset($post_string)) {
                            $out.= $post_string;
                        }

                        fwrite($fp, $out);
                        fclose($fp);

                        header('Content-Type: application/json; charset=utf-8');
                        echo Tools::jsonEncode(array(
                            'status' => 'in progress',
                            'output' => 'Successfully'
                        ));
                    } else {
                        $this->proccess();
                        $output .= $this->displayconfirmation($this->l('Successful operation!'));
                    }
                } catch (Exception $e) {
                    if ($isAjax) {
                        $output .= $e->getMessage();
                        header('Content-Type: application/json; charset=utf-8');
                        echo Tools::jsonEncode(array(
                            'status' => 'error',
                            'output' => $output
                        ));
                    } else {
                        $output .= $this->displayError($this->l($e->getMessage()));
                    }
                
                    $this->msgLog = $e->getMessage();
                    $this->writeLog();
                }

                $this->setConfig();//Updates the metadata configuration independentally
            }
        }

        if ($isAjax) {
            exit;
        }
        return $output .= $this->displayForm();
    }

    public function displayForm()
    {
        //If the definition of Authors (as Category or Tag) has yet to be defined
        if (Configuration::get('BBM_AUTOR_INSERT_TYPE') === false) {
            return $this->getFormInsertAutor();
        } else {
            return $this->getFormConfiguration();
        }
    }

    //The Features are personalised fields
    private function createFeaturesAndOptions()
    {
        $features = array(
//            'ISBN'             => 'ISBN',
//            'PUBLISHER_NAME'   => 'Editora',
//            'FORMAT_TYPE'      => 'Formato',
//            'EDITION_NUMBER'   => 'Edição',
//            'PROTECTION_TYPE'  => 'Proteção',
//            'IDIOM'            => 'Idioma',
//            'PAGES_NUMBER'     => 'Número de Páginas',
//            'AGE_RATING'       => 'Faixa Etária',
//            'COLLECTION_TITLE' => 'Coleção',
//            'AUTOR'            => 'Autor',
//            'ILUSTRADOR'       => 'Ilustrador'
            'PUBLISHER_NAME'    => 'Editora',
            'IDIOM'             => 'Idioma',
            'PAGES_NUMBER'      => 'Número de Páginas',
            'PROTECTION_TYPE'   => 'DRM'

        );

        foreach ($features as $key => $value) {
            $feature = new Feature();
            $feature->name[(int)Configuration::get('PS_LANG_DEFAULT')] = $value;
            $feature->add();

            if (!$feature->id) {
                return false;
            } else {
                //Associate ID to Feature
                if (!Configuration::updateValue('BBM_FEATURE_ID_' . $key, $feature->id)) {
                    return false;
                }
            }
        }

        //Sets Default Values for these options
        if (!Configuration::updateValue('BBM_OPTION_CLIENT_ID', null) ||
                !Configuration::updateValue('BBM_OPTION_CLIENT_SECRET', null) ||
                    !Configuration::updateValue('BBM_OPTION_OPERATION', 1) ||
                    !Configuration::updateValue('BBM_OPTION_ADD_EBOOKS_CAT', 1) ||
                        !Configuration::updateValue('BBM_OPTION_ENVIRONMENT', 1)) {
             return false;
        }
        return true;
    }

    //Categories are created dynamically, as it would be unviable to insert them previously in the database (Differently
    //from the Features), since they are innumerous. After creating them, they will be added to the Configuration Table
    //In case na ebook belongs to it, the ID is simply associated to it
    private function insertAutorBy($insertType)
    {
        if ($insertType == 1) { //If Tag, process is Ignored
            return true;
        } else if ($insertType == 2) { //Category
            //Create Author Category, takes and ID and adds to Configuration Table
            $category = new MYCategory();
            $category->name[(int)Configuration::get('PS_LANG_DEFAULT')] = 'Autor';
            $category->link_rewrite[(int)Configuration::get('PS_LANG_DEFAULT')] = 'Autor';
            $category->id_parent = Category::getRootCategory()->id;//Associates a Home Category
            $category->add();

            if (!$category->id) {
                return false;
            } else {
                $category->insertBBMCategory($category->id, 0);
            }

            if (!Configuration::updateValue('BBM_CATEGORY_ID_AUTOR', $category->id)) {
                return false;
            }
            return true;
        } else { //3 = No Definition
            // Ignored. Since they will not be inserted as Category or Tag, Authors
            // will remais as Feature.
            return true;
        }
    }

    private function getFormInsertAutor()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $fields_form = array();
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Configuration'),
            ),
            'input' => array(
                array(
                    'type'      => 'radio',
                    'label'     => $this->l('Insert Authors as: '),
                    'desc'      => $this->l('Even when opting for "None", authors will still be associated with ebooks through "customs features", making them possible to be displayed on the product page.'),
                    'name'      => 'autor_insert_type',
                    'class'     => 't',
                    'required'  => true,
                    'is_bool'   => false,
                    'values'    => array(
                        array(
                            'id'    => 'tag',
                            'value' => 1,
                            'label' => $this->l('Tag')
                        ),
                        array(
                            'id'    => 'categoria',
                            'value' => 2,
                            'label' => $this->l('Category')
                        ),
                        array(
                            'id'    => 'nenhum',
                            'value' => 3,
                            'label' => $this->l('None')
                        )
                    )
                )
            ),
            'submit' => array(
                'title' => $this->l('Proceed'),
                'class' => 'btn btn-default'
            )
        );

        $helper = new HelperForm();
         
        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
         
        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
         
        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit'.$this->name.'autor_insert_type';
        $helper->toolbar_btn = array(
            'save' =>
                array(
                    'desc' => $this->l('Proceed'),
                    'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                    '&token='.Tools::getAdminTokenLite('AdminModules'),
                ),
            'back' => array(
                    'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                    'desc' => $this->l('Back to list')
                )
        );

        $helper->fields_value['autor_insert_type'] = 1;//Tag como default
        $html = 'Tell us how you want the authors of our ebooks to appear in your store';

        return $html . $helper->generateForm($fields_form);
    }

    private function getFormConfiguration()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fields_form = array();
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Configuration'),
            ),
            'input' => array(
                array(
                    'type'      => 'text',
                    'label'     => $this->l('Identification Key'),
                    // 'desc'     => $this->l('Operation'),
                    'name'      => 'client_id',
                    'size'      => 20,
                    'required'  => true
                ),
                array(
                    'type'      => 'text',
                    'label'     => $this->l('Secret Key'),
                    'name'      => 'client_secret',
                    'size'      => 20,
                    'required'  => true
                ),
                array(
                    'type'      => 'radio',
                    'label'     => $this->l('Operation'),
                    'desc'      => $this->l('Select "complete" to import all our ebooks and "update" to import only those that were updated and / or removed yesterday.'),
                    'name'      => 'operation',
                    'required'  => true,
                    'class'     => 't',
                    'is_bool'   => true,
                    'values'    => array(
                        array(
                            'id'    => 'complete',
                            'value' => 1,
                            'label' => $this->l('Complete')
                        ),
                        array(
                            'id'    => 'update',
                            'value' => 2,
                            'label' => $this->l('Update')
                        )
                    )
                ),
                array(
                    'type'      => 'radio',
                    'label'     => $this->l('Environment'),
                    //'desc'      => $this->l('Sandbox for testing and Production for real!'),
                    'name'      => 'environment',
                    'required'  => true,
                    'class'     => 't',
                    'is_bool'   => true,
                    'values'    => array(
                        array(
                            'id'    => 'sandbox',
                            'value' => 1,
                            'label' => $this->l('Test')
                        ),
                        array(
                            'id'    => 'production',
                            'value' => 2,
                            'label' => $this->l('Production')
                        )
                    )
                ),
                array(
                    'type'      => 'radio',
                    'label'     => $this->l('Add ebooks category'),
                    //'desc'      => $this->l('Sandbox for testing and Production for real!'),
                    'name'      => 'add_ebooks_cat',
                    'required'  => true,
                    'class'     => 't',
                    'is_bool'   => true,
                    'values'    => array(
                        array(
                            'id'    => 'yes',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id'    => 'no',
                            'value' => 2,
                            'label' => $this->l('No')
                        )
                    )
                ),
            ),
            'submit' => array(
                'title' => $this->l('Import'),
                'class' => 'btn btn-default'
            ),
            'buttons' => array(
                'remove-all-ebooks' => array(
                    'title' => $this->l('Remove all ebooks'),
                    'name' => 'remove-all-ebooks',
                    'type' => 'button',
                    'class' => 'btn btn-default remove-all-ebooks pull-right',
                    'icon' => 'process-icon-cancel',
                ),
            ),
        );
     
        $helper = new HelperForm();
         
        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
         
        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
         
        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit'.$this->name.'operation';
        $helper->toolbar_btn = array(
            'save' =>
                array(
                    'desc' => $this->l('Save'),
                    'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                    '&token='.Tools::getAdminTokenLite('AdminModules'),
                ),
            'back' => array(
                    'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                    'desc' => $this->l('Back to list')
                )
            );
         
        // Loads/Sets Form Values, if they already exist.

        if ($this->clientID) {
            $helper->fields_value['client_id'] = trim(Configuration::get('BBM_OPTION_CLIENT_ID'));
        } else {
            $helper->fields_value['client_id'] = '';
        }
        if ($this->clientSecret) {
            $helper->fields_value['client_secret'] = trim(Configuration::get('BBM_OPTION_CLIENT_SECRET'));
        } else {
            $helper->fields_value['client_secret'] = '';
        }
        if ($this->operation == 1) {
            $helper->fields_value['operation'] = 1;
        } else if ($this->operation == 2) {
            $helper->fields_value['operation'] = 2;
        }
        if ($this->environment == 1) {
            $helper->fields_value['environment'] = 1;
        } else if ($this->environment == 2) {
            $helper->fields_value['environment'] = 2;
        }

        if ($this->addEbooksCat == 1) {
            $helper->fields_value['add_ebooks_cat'] = 1;
        } else {
            $helper->fields_value['add_ebooks_cat'] = 2;
        }

        $html = 'Attention! Importing may take several minutes';
        return $html . $helper->generateForm($fields_form);
    }

    //Adds Fields to identify which Product is Bibliomundi´s
    private function createCustomFieldsToDB()
    {
        $sql= "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "bbm_product`
        (
            `id` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_product` INT(10) NOT NULL,
            `bbm_id_product` VARCHAR(10) NOT NULL,
            `iso_code` VARCHAR(3)
        )";
       
        if (!Db::getInstance()->Execute($sql)) {
            return false;
        }

        $sql= "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "bbm_category`
        (
            `id` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_category` INT(10) NOT NULL,
            `bbm_id_category` VARCHAR(10) NOT NULL
        )";
       
        if (!Db::getInstance()->Execute($sql)) {
            return false;
        }
        return true;
    }

    //Keeps quantity at 0 which is a workaround
    public function hookActionOrderStatusPostUpdate($params)
    {
        foreach ($params['cart']->getProducts() as $product) {
            if (MYProduct::getIDBBMByID($product['id_product'])) {
                StockAvailableCore::setQuantity($product['id_product'], $product['id_product_attribute'], 0);
            }
        }
    }

    //Checkout here
    public function hookActionPaymentConfirmation($params)
    {
        $bbmEbooks = array();
        foreach ($params['cart']->getProducts() as $product) {
            if ($idBBM = MYProduct::getIDBBMByID($product['id_product'])) {
                $bbmEbooks[$idBBM] = $product;
            }
        }
        if (count($bbmEbooks)) {
            try {
                $invoiceDate = Db::getInstance()->getValue('SELECT `invoice_date` FROM `' . _DB_PREFIX_ . 'orders` WHERE `id_order` = ' . $params['id_order']);
                $invoiceTime = (!empty($invoiceDate) && ($invoiceDate != '0000-00-00 00:00:00') ) ? strtotime($invoiceDate) : time();

                $purchase = $this->_validatePurchase($bbmEbooks, $params);
                $purchase->checkout('PRESTA_BBM_' . $params['id_order'], $invoiceTime);
            } catch (Exception $e) {
                PrestaShopLogger::addLog($e->getMessage(), 3);
                throw new PrestaShopException('There was an internal problem. We removed the cart for you. Sorry for the inconvenience!');
            }
        }
    }

    //Validate order
    public function hookActionValidateOrder($params)
    {
        $bbmEbooks = array();
        foreach ($params['cart']->getProducts() as $product) {
            if ($idBBM = MYProduct::getIDBBMByID($product['id_product'])) {
                $bbmEbooks[$idBBM] = $product;
            }
        }
        if (count($bbmEbooks)) {
            try {
                $this->_validatePurchase($bbmEbooks, $params);
            } catch (Exception $e) {
                PrestaShopLogger::addLog($e->getMessage(), 3);
                throw new PrestaShopException('There was an internal problem. We removed the cart for you. Sorry for the inconvenience!');
            }
        }
    }

    public function hookDisplayBeforePayment($params)
    {
        $bbmEbooks = array();

        foreach ($params['cart']->getProducts() as $product) {
            if ($idBBM = MYProduct::getIDBBMByID($product['id_product'])) {
                $bbmEbooks[$idBBM] = $product;
            }
        }
        if (count($bbmEbooks)) {
            try {
                $this->_validatePurchase($bbmEbooks, $params);
            } catch (Exception $e) {
                $errors = array();
                //Regardless of the error, all ebooks are curretly removed from the basket as the API is not informing what error is the cause.
                foreach ($bbmEbooks as $ebook) {
                    $errors[] = $ebook['name'];

                    //Remove from Shopping Cart
                    $where = pSQL('`id_product` = ' . pSQL((int)$ebook['id_product']) .
                             ' AND `id_cart` = ' . pSQL((int)$params['cart']->id));

                    Db::getInstance()->delete('cart_product', $where);
                }

                $this->context->controller->errors[] = Tools::displayError('There was an internal problem with the following ebooks: ' . implode(',', $errors) . '. We removed the cart for you. Sorry for the inconvenience!', !Tools::getValue('ajax'));
            }
        }
    }

/**
 * The two hooks below, together are necessary for:
 *
 * ActionBeforeCartUpdateQty hook, defines if a Product was Added, Romoved or it is located in the Cart
 * ActionCartSave hook, is executed when a Product is Added, Remvoed, Deleted or Updated.
 *
 */
    public function hookActionCartSave()
    {
        if (isset($this->cartErrorNumber)) {
            switch ($this->cartErrorNumber) { //It might be required to create na alternative than this simple Delete and Update
                case 1:
                    $where = pSQL('`id_product` = ' . pSQL((int)$this->cartParams['product']->id) .
                             ' AND `id_cart` = ' . pSQL((int)$this->cartParams['cart']->id));

                    Db::getInstance()->delete('cart_product', $where);
                    break;
                case 2:
                    $where = pSQL('`id_product` = ' . pSQL((int)$this->cartParams['product']->id) .
                             ' AND `id_cart` = ' . pSQL((int)$this->cartParams['cart']->id));

                    Db::getInstance()->update('cart_product', array(
                        'quantity' => 1,
                        'date_add' => "NOW()"
                        ), $where);
                    break;
            }
            $this->context->controller->errors[] = Tools::displayError("You can not purchase more than 1 unit of the product \"{$this->cartParams['product']->name}\"", !Tools::getValue('ajax'));
        } else if (isset($this->cartParams) && $this->cartParams['operator'] == 'up') {
            try {
                // Validate if product can be purchase on bibliomundi or not
                $this->_validateProductAfterAdd();
            } catch (Exception $e) {
                $where = pSQL('`id_product` = ' . pSQL((int)$this->cartParams['product']->id) .
                    ' AND `id_cart` = ' . pSQL((int)$this->cartParams['cart']->id));

                Db::getInstance()->delete('cart_product', $where);
                $this->context->controller->errors[] = Tools::displayError("This product can\'t be purchase at the moment. Sorry for the inconvenience!");
            }
        }
    }

    public function hookActionBeforeCartUpdateQty($params)
    {
        $this->{'cartParams'} = $params;
        $this->{'cartErrorNumber'} = null;
        //1- Product is Bibliomundi´s, it is not in the Shopping Cart and its quantity is greater than 1
        //2 - Product is Bibliomundi´s, it is already in the Shopping Cart and operation is addition
        
        if (MYProduct::getIDBBMByID($this->cartParams['product']->id)) {
            $isInCart = $this->cartParams['cart']->containsProduct($this->cartParams['product']->id);

            //Rule 1
            if (!$isInCart && $this->cartParams['quantity'] > 1) {
                $this->cartErrorNumber = 1;
            } else if ($isInCart && $this->cartParams['operator'] == 'up') { //Rule 2
                $this->cartErrorNumber = 2;
            }
        }
    }

    public function hookActionDownloadBBMFile($params)
    {
        $download = new \BBM\Download($this->clientID, $this->clientSecret);
        $download->environment = $this->environmentAlias[$this->environment];
        //$download->verbose(true);

        $data = array(
            'ebook_id' => (int) $params['id_bbm_product'],
            'transaction_time' => time(),//$params['invoice_time'],
            'transaction_key' => 'PRESTA_BBM_' . $params['id_order']
        );

        try {
            $download->validate($data);
            $download->download();
        } catch (\BBM\Server\Exception $e) {
            return $e->getMessage();
        }
    }

    public function hookActionProductDelete($params)
    {
        $where = pSQL('`id_product` = ' . pSQL((int)$params['id_product']));

        Db::getInstance()->delete('bbm_product', $where);

        return true;
    }

    public function hookActionCategoryDelete($params)
    {
        $where = pSQL('`id_product` = ' . pSQL((int)$params['category']->id_category));

        Db::getInstance()->delete('bbm_category', $where);

        return true;
    }

    public function loadFiles()
    {
        require_once dirname(__FILE__) . '/classes/MYProduct.php';
        require_once dirname(__FILE__) . '/classes/MYCategory.php';
        require_once dirname(__FILE__) . '/classes/MYImage.php';
        require_once dirname(__FILE__) . '/classes/MYCustomer.php';
        require_once dirname(__FILE__) . '/lib/api-client-side/autoload.php';
        require_once dirname(__FILE__) . '/lib/bbm-onix-parser/autoload.php';
        return true;
    }

    //Deletes all Bibliomundi additions from the Database
    private function deleteFromDB()
    {
        $categories = Db::getInstance()->executeS('SELECT `id_category` FROM `' . _DB_PREFIX_ . 'bbm_category`');
        $products   = Db::getInstance()->executeS('SELECT `id_product`  FROM `' . _DB_PREFIX_ . 'bbm_product`');//Products are responsible for deleting Tags

        $category = new Category();
        $product  = new Product();
        $feature  = new Feature();

        if (count($categories)) {
            $category->deleteSelection(array_map(function ($category) {
                return $category['id_category'];
            }, $categories));//Returns multidimentional Array
        }

        if (count($products)) {
            $product->deleteSelection(array_map(function ($product) {
                return $product['id_product'];
            }, $products));//Ditto
        }

        $feature->deleteSelection(
            array(
                //$this->featureIDISBN,
                $this->featureIDPublisherName,
                //$this->featureIDFormatType,
                //$this->featureIDEditionNumber,
                $this->featureIDIdiom,
                $this->featureIDPagesNumber,
                $this->featureIDProtectionType,
                //$this->featureIDAgeRating,
                //$this->featureIDCollectionTitle,
                //$this->featureIDAutor,
                //$this->featureIDIlustrador
            )
        );

        Db::getInstance()->delete('configuration', "name LIKE 'BBM_%'");
        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'bbm_product`');
        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'bbm_category`');
        return true;
    }

    public function writeLog()
    {
        if (!empty($this->msgLog)) {
            $fp = fopen(dirname(__FILE__) . "/log/{$this->operationAlias[$this->operation]}.txt", 'a');
            fwrite($fp, date('Y-m-d H:i:s') . ' - ' . $this->msgLog . "\n");
            fclose($fp);
        }
    }
    public static function _updateImportLog($result)
    {
        $lock = fopen(dirname(__FILE__).'/log/import.lock', 'w');
        //ftruncate($lock, 0);
        if (flock($lock, LOCK_EX)) {
            ftruncate($lock, 0);
            fwrite($lock, Tools::jsonEncode($result));
            fflush($lock);
            flock($lock, LOCK_UN);
        }

        fclose($lock);
    }

    private function _validateProductAfterAdd()
    {
        try {
            $products = $this->context->cart->getProducts();
            $bbmEbooks = array();
            foreach ($products as $product) {
                if ($idBBM = MYProduct::getIDBBMByID($product['id_product'])) {
                    $bbmEbooks[$idBBM] = $product;
                }
            }
            $this->_validatePurchase($bbmEbooks, array('cart' => $this->context->cart));
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function _validatePurchase($bbmEbooks, $params)
    {
        try {
            $purchase = new \BBM\Purchase($this->clientID, $this->clientSecret);

            $purchase->environment = $this->environmentAlias[$this->environment];

            //$purchase->verbose(true);

            $customer = new MYCustomer($params['cart']->id_customer);

            $address = $customer->getMYAddresses((int)Configuration::get('PS_LANG_DEFAULT'), $params['cart']->id_address_delivery);
            if (empty($address)) { // Fake address for validate only.
                $address[0]['postcode'] = '000000';
                $address[0]['country_iso'] = 'BR';
            }
            $gender = array(1 => 'm', 2 => 'f');

            preg_match_all('!\d+!', $address[0]['postcode'], $zipCode);

            $birthDay = ($this->context->customer->birthday == "0000-00-00") ? "1970-01-01" : $this->context->customer->birthday;
            $idGender = ($customer->id_gender) ? $customer->id_gender : 1;

            $bbmCustomer = array(
                'customerIdentificationNumber' => (int)$customer->id, // INT, YOUR STORE CUSTOMER ID
                'customerFullname' => $customer->firstname . ' ' . $this->context->customer->lastname, // STRING, CUSTOMER FULL NAME
                'customerEmail' => $customer->email, // STRING, CUSTOMER EMAIL
                'customerGender' => $gender[$idGender], // ENUM, CUSTOMER GENDER, USE m OR f (LOWERCASE!! male or female)
                'customerBirthday' => str_replace('-', '/', $birthDay), // STRING, CUSTOMER BIRTH DATE, USE Y/m/d (XXXX/XX/XX)
                'customerCountry' => $address[0]['country_iso'], // STRING, 2 CHAR STRING THAT INDICATE THE CUSTOMER COUNTRY (BR, US, ES, etc)
                'customerZipcode' => implode('', $zipCode[0]), // STRING, POSTAL CODE, ONLY NUMBERS
                'customerState' => !empty($address[0]['state_iso']) ? $address[0]['state_iso'] : 'RJ' // STRING, 2 CHAR STRING THAT INDICATE THE CUSTOMER STATE (RJ, SP, NY, etc)
            );

            $purchase->setCustomer($bbmCustomer);
            foreach ($bbmEbooks as $key => $ebook) {
                $purchase->addItem($key, $ebook['price'], MYProduct::getIsoCodeByIDBBM($key));//Bibliomundi ID and Price
            }
            $purchase->validate();
        } catch (Exception $e) {
            PrestaShopLogger::addLog($e->getMessage(), 3);
            throw $e;
        }
        return $purchase;
    }

//    public function hookDisplayProductTab($params)
//    {
//        $this->context->smarty->assign(
//            array(
//                //'my_module_name' => Configuration::get('MYMODULE_NAME'),
//                'my_module_link' => $this->context->link->getModuleLink('bibliomundi', 'display')
//            )
//        );
//        return $this->display(__FILE__, 'mymodule.tpl');
//    }

    private function _getEbookCategoryId()
    {
        if ($this->addEbooksCat == 1) {
            $ebookCats = Category::searchByName((int)Configuration::get('PS_LANG_DEFAULT'), 'eBooks');
            if (!empty($ebookCats)) {
                $ebookCat = array_shift($ebookCats);
                return $ebookCat["id_category"];
            } else {
                $category = new MYCategory();
                $category->name[(int)Configuration::get('PS_LANG_DEFAULT')] = 'eBooks';
                $category->link_rewrite[(int)Configuration::get('PS_LANG_DEFAULT')] = Tools::link_rewrite('ebooks');
                $category->id_parent = Category::getRootCategory()->id;//Associates a Default Category, which usually is Home
                $category->add();
                return $category->id;
            }
        } else {
            return Category::getRootCategory()->id;//Associates a Default Category, which usually is Home
        }
    }
}

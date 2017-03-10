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

if (!defined('_PS_VERSION_'))
	exit;

class Bibliomundi extends Module
{
	public $clientID;
	public $clientSecret;
	public $operation;
	public $environment;
	public $operationAlias = array(1 => 'complete', 2 => 'updates');
	public $environmentAlias = array(1 => 'sandbox', 2 => 'production');

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

   /*
    *
	* The Author will be inserted as Feature by default, but we will still allow
	* The user to choose between inserting authors as Categories or Tags
	*
	*/

	public $categoryIDAutor;//Category ID in which Author will be Parent of other inserted Authors
	

	public function __construct()
	{
		$this->name = 'bibliomundi';
		$this->version = '1.0.0';
		$this->author = 'Bibliomundi';
		$this->tab = 'others';
		$this->displayName = $this->l('Integration with Bibliomundi ebooks');
    	$this->description = $this->l('Digital book distributor.');
    	$this->module_key = 'ce2baff2d6e63819c47e7af943a5d702';
    	$this->ps_versions_compliancy = array('min' => '1.6');
    	$this->confirmUninstall = $this->l('Are you sure you want to uninstall our module?');
		$this->bootstrap = true;
		parent::__construct();
		$this->loadFiles();
		$this->getConfig();

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
		 	 		 					!$this->registerHook('actionOrderStatusPostUpdate'))

	    	return false;

 		return true;
	}

	/*public function createCategories() {

	    $arrCategories = array();
	    $arrSubCategories = array();

	    $file = fopen(_PS_MODULE_DIR_.$this->name.'/csv/bisac_categories.csv', 'r');
		while (($line = fgetcsv($file, 1000, ';')) !== FALSE) {
			if(!is_numeric($line[0])){
				$arrKey = $line;
			}else{
				$arrCategories[] = array_combine($arrKey, $line);
			}
		}
		fclose($file);

		$file = fopen(_PS_MODULE_DIR_.$this->name.'/csv/bisac_subcategories.csv', 'r');
		while (($line = fgetcsv($file, 1000, ';')) !== FALSE) {
			if(!is_numeric($line[0])){
				$arrKey = $line;
			}else{
				$arrSubCategories[$line[1]][] = array_combine($arrKey, $line);
			}
		}
		fclose($file);

		$cat = $subCat = new MYCategory();

        foreach ($arrCategories as $category) {

        	$cat->is_bbm = true;
        	$cat->bbm_id_category = $category['bisac_code'];
        	$cat->id_parent = Category::getRootCategory()->id;
        	$cat->name[(int)Configuration::get('PS_LANG_DEFAULT')] = $category['bisac_description'];
        	$cat->link_rewrite[(int)Configuration::get('PS_LANG_DEFAULT')] = Tools::link_rewrite($category['description_eng']);
        	$cat->add();

        	foreach ($arrSubCategories[$category['id']] as $subCategories) {

        		$subCat->is_bbm = true;
        		$subCat->bbm_id_category = $subCategories['code'];
        		$subCat->id_parent = (int)$cat->id;
        		$subCat->name[(int)Configuration::get('PS_LANG_DEFAULT')] = $subCategories['description_ptbr'];
	        	$subCat->link_rewrite[(int)Configuration::get('PS_LANG_DEFAULT')] = Tools::link_rewrite($subCategories['description_en']);
	        	$subCat->add();
        	}
        }
	}*/

	public function uninstall()
	{
		if (!parent::uninstall() || !$this->deleteFromDB())
	    	return false;
	  
		return true;
	}
	
	private function setConfig()
	{
		/* These are the only values that may be updated after insatallation */

        Configuration::updateValue('BBM_OPTION_CLIENT_ID', $this->clientID);
        Configuration::updateValue('BBM_OPTION_CLIENT_SECRET', $this->clientSecret);
        Configuration::updateValue('BBM_OPTION_OPERATION', $this->operation);
        Configuration::updateValue('BBM_OPTION_ENVIRONMENT', $this->environment);
	}

	private function getConfig()
	{
		/* Complete verification even if, sometimes, the return might be NULL */

		$this->clientID 				= Configuration::get('BBM_OPTION_CLIENT_ID');
		$this->clientSecret 			= Configuration::get('BBM_OPTION_CLIENT_SECRET');
		$this->operation    			= Configuration::get('BBM_OPTION_OPERATION');
		$this->environment  			= Configuration::get('BBM_OPTION_ENVIRONMENT');

		$this->featureIDISBN			= Configuration::get('BBM_FEATURE_ID_ISBN');
		$this->featureIDPublisherName	= Configuration::get('BBM_FEATURE_ID_PUBLISHER_NAME');
		$this->featureIDFormatType		= Configuration::get('BBM_FEATURE_ID_FORMAT_TYPE');
		$this->featureIDEditionNumber	= Configuration::get('BBM_FEATURE_ID_EDITION_NUMBER');
		$this->featureIDIdiom			= Configuration::get('BBM_FEATURE_ID_IDIOM');
		$this->featureIDPagesNumber		= Configuration::get('BBM_FEATURE_ID_PAGES_NUMBER');
		$this->featureIDProtectionType	= Configuration::get('BBM_FEATURE_ID_PROTECTION_TYPE');
		$this->featureIDAgeRating		= Configuration::get('BBM_FEATURE_ID_AGE_RATING');
		$this->featureIDCollectionTitle	= Configuration::get('BBM_FEATURE_ID_COLLECTION_TITLE');
		$this->featureIDAutor			= Configuration::get('BBM_FEATURE_ID_AUTOR');
		$this->featureIDIlustrador		= Configuration::get('BBM_FEATURE_ID_ILUSTRADOR');
		
		if(Configuration::get('BBM_AUTOR_INSERT_TYPE') == 2)
		{
			$aux = Configuration::get('BBM_CATEGORY_ID_AUTOR');

			if(!Category::categoryExists($aux))//In case the user has deleted the Author Category
			{
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

		try
		{
		    $catalog->validate();
		    return $catalog->get();
		}
		catch(Exception $e)
		{
		    throw $e;
		}
	}

	public function ajax_valid()
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
		
		$lock = fopen(dirname(__FILE__).'/log/import.lock', 'a');
		ftruncate($lock, 0);
		fwrite($lock, Tools::jsonEncode($result));
		fclose($lock);
		
		try
		{
			set_time_limit(0);//Avoids timeout, considering there will be a number of operations

			//header('Content-Type: application/xml; charset=utf-8'); echo $this->getCatalog(); exit;
			$parser = new \BBMParser\OnixParser($this->getCatalog());

			//d($parser->getOnix()->getProductsAvailable());

			if(!$productsAvailable = $parser->getOnix()->getProductsAvailable())
				throw new Exception("There are no ebooks to import!");

			$result['total'] = count($productsAvailable);
			$result['current'] = 0;

			//Be it Complete or Update, it will all be here!	
			foreach($productsAvailable as $bbmProduct)
			{
				$result['current'] = $result['current'] + 1;
				{
					$lock = fopen(dirname(__FILE__).'/log/import.lock', 'a');
					ftruncate($lock, 0);
					fwrite($lock, Tools::jsonEncode($result));
					fclose($lock);
				}
				$this->product_iso_code = '';
				//d($bbmProduct);
				$product = new MYProduct();
				$idProductAlreadyInserted = MYProduct::getIDByIDBBM($bbmProduct->getId());//Verifies if it exists

				//Avoids duplicated entries
				if($idProductAlreadyInserted && $bbmProduct->getOperationType() == '03')
					continue;

				if($idProductAlreadyInserted && $bbmProduct->getOperationType() == '04')
					$product = new MYProduct($idProductAlreadyInserted);

				//In case the process of Updated includes the Deletion of a Product
				if($bbmProduct->getOperationType() == '05')
				{
					if($idProductAlreadyInserted)
					{
						$product = new MYProduct($idProductAlreadyInserted);
						$product->delete();
						continue;//Moves on to next ebook
					}
					else
						continue;
				}

				$product->name[(int)Configuration::get('PS_LANG_DEFAULT')] = $bbmProduct->getTitle();
		        $product->meta_keywords[(int)Configuration::get('PS_LANG_DEFAULT')] = $bbmProduct->getTitle();
		        $product->link_rewrite[(int)Configuration::get('PS_LANG_DEFAULT')] = Tools::link_rewrite($bbmProduct->getTitle());

				$current = Currency::getCurrent();
	        	$bbmPrice = array();
		        foreach ($bbmProduct->getPrices() as $price) {		        	
		        	$bbmPrice[$price->getCurrency()] = $price->getAmount();
		        }
		        if(in_array($current->iso_code, array_keys($bbmPrice))){
		        	$product->price = $bbmPrice[$current->iso_code];
		        	$this->product_iso_code = $current->iso_code;
		        }else{
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
				$tags 		   = $bbmProduct->getTags();//If there are no Tags, a empty array is returned

				//Topics (Categories)
				if(count($bbmProduct->getCategories()))
				{
					foreach ($bbmProduct->getCategories() as $bbmCategory)
					{
						$category = new MYCategory();

						if($id = MYCategory::getIDByIDBBM($bbmCategory->getCode()))
							$category = new MYCategory($id);
						else //Insert a new Category
						{
							$category->name[(int)Configuration::get('PS_LANG_DEFAULT')] = $bbmCategory->getName();
							$category->link_rewrite[(int)Configuration::get('PS_LANG_DEFAULT')] = Tools::link_rewrite($bbmCategory->getName());
							$category->id_parent = Category::getRootCategory()->id;//Associates a Default Category, which usually is Home
							$category->add();
							$category->insertBBMCategory($category->id, $bbmCategory->getCode());
						}
						$categoriesIds[] = $category->id;
					}
				}
				
				//According to Premisses, the Author MAY NOT CHANGE, therefore we ignore them on Update routine
				if($bbmProduct->getOperationType() == '03')
				{
					if(Configuration::get('BBM_AUTOR_INSERT_TYPE') != 3)//3 Keeps Author as Feature
					{
						//Contributors
						if(count($bbmProduct->getContributors()))
						{
							foreach ($bbmProduct->getContributors() as $contributor)
							{
								if($contributor instanceof \BBMParser\Model\Author)
								{
									//Authors are inserted as Category, Tag, or simply leave them as a Feature

									if(Configuration::get('BBM_AUTOR_INSERT_TYPE') == 1)//Insert as Tag
										$tags[] = $contributor->getFullName();
									else if(Configuration::get('BBM_AUTOR_INSERT_TYPE') == 2)//Insert as Category
									{
										$category = new MYCategory();

										if($id = MYCategory::getIDByIDBBM($contributor->getId()))
											$category = new MYCategory($id);
										else //Inserts a new Category
										{
											$category->name[(int)Configuration::get('PS_LANG_DEFAULT')] = $contributor->getFullName();
											$category->link_rewrite[(int)Configuration::get('PS_LANG_DEFAULT')] = Tools::link_rewrite($contributor->getFullName());
											$category->id_parent = $this->categoryIDAutor;//Associates the Default Category as Author
											$category->add();
											$category->insertBBMCategory($category->id, $contributor->getId());
										}
										$categoriesIds[] = $category->id;
									}
								}
								/*else if($contributor instanceof \BBMParser\Model\Ilustrador)
								{
									Defines that the only Contributors that may be a Category are Authors
								}*/
							}
						}
					}
				}

				$product->id_category_default = $categoriesIds[0];//There are no rules set here. Selection may be random

				if($idProductAlreadyInserted && $bbmProduct->getOperationType() == '04')
				{
					$product->deleteFeatures();//This is how Prestashop works internally. Deletes all Features to then recreate them.
					$product->update();
				}
				else
				{
					$product->add();//Add before to gain access to Insert_ID to permit operations below
				}

				$product->insertBBMProduct($product->id, $bbmProduct->getId(), $this->product_iso_code);

				//Associate all Tags, including the Author, if it´s the case, to the Product.
				//OBS: If there is already a Tag with the same name, Prestashop ignores this addition. Which is excelent for Update routine.
				if(count($tags))
					Tag::addTags((int)Configuration::get('PS_LANG_DEFAULT'), $product->id, $tags);

				//Associates Product to all created Categories. If they exist, association is ignored!
				$product->addToCategories($categoriesIds);

				//Rules for addition and association of Custom Features

				//1- Creates a Feature and collects its ID
				//2- Creates a Value Feature by addFeaturesToDB function and collects ID
				//3- Associates Value Feature to Product and language through addFeaturesCustomToDB function

				$idFeatureValue = $product->addFeaturesToDB($this->featureIDISBN, null, 1);
				$product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getISBN());

				$idFeatureValue = $product->addFeaturesToDB($this->featureIDIdiom, null, 1);
				$product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getIdiom());

				if($bbmProduct->getCollectionTitle())
				{
					$idFeatureValue = $product->addFeaturesToDB($this->featureIDCollectionTitle, null, 1);
					$product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getCollectionTitle());
				}

				$idFeatureValue = $product->addFeaturesToDB($this->featureIDAgeRating, null, 1);
				$product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getAgeRatingValue());

				$idFeatureValue = $product->addFeaturesToDB($this->featureIDProtectionType, null, 1);
				$product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getProtectionType());

				$idFeatureValue = $product->addFeaturesToDB($this->featureIDPagesNumber, null, 1);
				$product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getPageNumbers());

				$idFeatureValue = $product->addFeaturesToDB($this->featureIDEditionNumber, null, 1);
				$product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getEditionNumber());

				$idFeatureValue = $product->addFeaturesToDB($this->featureIDFormatType, null, 1);
				$product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getFormatType());

				$idFeatureValue = $product->addFeaturesToDB($this->featureIDPublisherName, null, 1);
				$product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getImprintName());

				//Inserts Authors separated by common commas ","
				if($autorsName = implode(',', array_map(array('\BBMParser\Model\Contributor', 'getFullNameStatically'), $bbmProduct->getContributorsByType('Autor'))))
				{
					$idFeatureValueAutor = $product->addFeaturesToDB($this->featureIDAutor, null, 1);
					$product->addFeaturesCustomToDB($idFeatureValueAutor, 1, $autorsName);
				}

				//Inserts Illustrators separated by common commas ","
				if($ilustradorsName = implode(',', array_map(array('\BBMParser\Model\Contributor', 'getFullNameStatically'), $bbmProduct->getContributorsByType('Ilustrador'))))
				{
					$idFeatureValueIlustrador = $product->addFeaturesToDB($this->featureIDIlustrador, null, 1);
					$product->addFeaturesCustomToDB($idFeatureValueIlustrador, 1, $ilustradorsName);
				}

				/*
				 * Download
				 */						

				if($bbmProduct->getOperationType() == '03')
				{
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
				if($images = $product->getImages((int)Configuration::get('PS_LANG_DEFAULT')))
				{
					foreach ($images as $img)
					{
						$image = new MYImage($img['id_image']);
						$image->delete();
					}
				}

				$image = new MYImage();
				$image->id_product = $product->id;
				$image->position = MYImage::getHighestPosition($product->id) + 1;
				$image->cover = true;
				$image->legend = $bbmProduct->getTitle();
				if (($image->validateFields(false, true)) === true &&
				($image->validateFieldsLang(false, true)) === true && $image->add())
				{
				    //$image->associateTo($shops);
				    if (!$image->copy($product->id, $image->id, $bbmProduct->getUrlFile(), 'products', true))
				    {
				        $image->delete();
				    }
				}				

				//throw new Exception("Error Processing Request", 1);
				
			}
			
			$result['status'] = 'complete';
			$result['content'] = $this->l('Successful operation!');
		}
		catch(Exception $e)
		{
			$result['status'] = 'error';
			$result['content'] = $e->getMessage();
			
			throw $e;
		}
		
		$lock = fopen(dirname(__FILE__).'/log/import.lock', 'a');
		ftruncate($lock, 0);
		fwrite($lock, Tools::jsonEncode($result));
		fclose($lock);
		
	}

	public function getContent()
	{
		$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
		$output = '';

		//When the initial definition  form is issued (For now, only the addition of Authors)
		if(Tools::isSubmit('submit' . $this->name . 'autor_insert_type'))
		{
			if(in_array(Tools::getValue('autor_insert_type'), array('1', '2', '3')))//0 = No definition, 1 = Category, 2 = Tag
			{
				if(!$this->insertAutorBy(Tools::getValue('autor_insert_type')))
					$output .= $this->displayError($this->l('Internal error!'));
				else
					Configuration::updateValue('BBM_AUTOR_INSERT_TYPE', Tools::getValue('autor_insert_type'));
			}
			else
				$output .= $this->displayError($this->l('Invalid author type!'));

		}
		else if (Tools::isSubmit('submit' . $this->name . 'operation'))
	    {
	        if (!Tools::getValue('client_id') || !Tools::getValue('client_secret'))
	            $output .= $this->displayError($this->l('Unidentified ID Key or Secret Key!'));
	        else if(!in_array(Tools::getValue('operation'), array('1', '2')) || !in_array(Tools::getValue('environment'), array('1', '2')))
	        	$output .= $this->displayError($this->l('Invalid Operation Type or Environment!'));
	        else
	        {
	        	$this->clientID       = (string)Tools::getValue('client_id');
		        $this->clientSecret   = (string)Tools::getValue('client_secret');
		        $this->operation 	  = (string)Tools::getValue('operation');
		        $this->environment    = (string)Tools::getValue('environment');

	        	try
	        	{
					$result = array('status' => 'free');
					
					if (file_exists(dirname(__FILE__).'/log/import.lock'))
					{
						$result = Tools::jsonDecode(Tools::file_get_contents(dirname(__FILE__).'/log/import.lock'));
						if ($result->status == 'in progress') {
							if (time() - filemtime(dirname(__FILE__).'/log/import.lock') > 5)
							{
								unlink(dirname(__FILE__).'/log/import.lock');
								// restart
							}
							else
							{
								header('Content-Type: application/json; charset=utf-8');
								echo Tools::jsonEncode(array(
									'status' => 'in progress',
									'output' => 'Successfully'
								));
								exit;
							}
						}
					}
					
					if($isAjax)
					{
						$post_data = array(
							'clientID' => $this->clientID,
							'clientSecret' => $this->clientSecret,
							'operation' => $this->operation,
							'environment' => $this->environment
						);
						$post_params = array();
						foreach ($post_data as $key => &$val) {
						  if (is_array($val)) $val = implode(',', $val);
							$post_params[] = $key.'='.urlencode($val);
						}
						
						$post_string = implode('&', $post_params);

						$parts = parse_url($_SERVER['HTTP_ORIGIN']);

						$fp = fsockopen($parts['host'],
							isset($parts['port'])?$parts['port']:80,
							$errno, $errstr, 30);

						$out = "POST /modules/bibliomundi/bibliomundi-import.php HTTP/1.1\r\n";
						$out.= "Host: ".$_SERVER['HTTP_HOST']."\r\n";
						$out.= "Cookie: ".$_SERVER['HTTP_COOKIE']."\r\n";
						$out.= "User-Agent: ".$_SERVER['HTTP_USER_AGENT']."\r\n";
						$out.= "Content-Type: application/x-www-form-urlencoded\r\n";
						$out.= "Content-Length: ".Tools::strlen($post_string)."\r\n";
						$out.= "Connection: Close\r\n\r\n";
						if (isset($post_string)) $out.= $post_string;

						fwrite($fp, $out);
						fclose($fp);

						header('Content-Type: application/json; charset=utf-8');
						echo Tools::jsonEncode(array(
							'status' => 'in progress',
							'output' => 'Successfully'
						));
					}
					else
					{
						$this->proccess();
						
						$output .= $this->displayconfirmation($this->l('Successful operation!'));
					}
	        	}
	        	catch(Exception $e)
	        	{
	        		$this->{'msgLog'} = $e->getMessage();
					
					if ($isAjax)
					{
						$output .= $e->getMessage();
						
						header('Content-Type: application/json; charset=utf-8');
						echo Tools::jsonEncode(array(
							'status' => 'error',
							'output' => $output
						));
					}
					else
					{
						$output .= $this->displayError($this->l($e->getMessage()));
					}
	        	}
	        	
        		$this->writeLog();
        		$this->setConfig();//Updates the metadata configuration independentally
	        }
	    }

		if ($isAjax) exit;
		
	    return $output .= $this->displayForm();
	}

	public function displayForm()
	{	    
	    //If the definition of Authors (as Category or Tag) has yet to be defined
	    if(Configuration::get('BBM_AUTOR_INSERT_TYPE') === false)
		{
			return $this->getFormInsertAutor();
		}
		else
		{
		    return $this->getFormConfiguration();
	    }
	}

	//The Features are personalised fields
	private function createFeaturesAndOptions()
	{
		$features = array
		(
			'ISBN' 			   => 'ISBN',
			'PUBLISHER_NAME'   => 'Editora',
			'FORMAT_TYPE'      => 'Formato',
			'EDITION_NUMBER'   => 'Edição',
			'PROTECTION_TYPE'  => 'Proteção',
			'IDIOM' 		   => 'Idioma',
			'PAGES_NUMBER'	   => 'Número de Páginas',
			'AGE_RATING' 	   => 'Faixa Etária',
			'COLLECTION_TITLE' => 'Coleção',
			'AUTOR' 		   => 'Autor',
			'ILUSTRADOR' 	   => 'Ilustrador'
		);

		foreach ($features as $key => $value)
	    {
		    $feature = new Feature();
		    $feature->name[(int)Configuration::get('PS_LANG_DEFAULT')] = $value;
		    $feature->add();

    	    if(!$feature->id)
	    		return false;
	    	else
	    	{
	    		//Associate ID to Feature
	    		if(!Configuration::updateValue('BBM_FEATURE_ID_' . $key, $feature->id))
	    			return false;
	    	}
 		}

		//Sets Default Values for these options
 		if(!Configuration::updateValue('BBM_OPTION_CLIENT_ID', null) ||
        	!Configuration::updateValue('BBM_OPTION_CLIENT_SECRET', null) ||
        		!Configuration::updateValue('BBM_OPTION_OPERATION', 1) ||
        			!Configuration::updateValue('BBM_OPTION_ENVIRONMENT', 1))
 			return false;

 		return true;
	}

	//Categories are created dynamically, as it would be unviable to insert them previously in the database (Differently
	//from the Features), since they are innumerous. After creating them, they will be added to the Configuration Table
	//In case na ebook belongs to it, the ID is simply associated to it
	private function insertAutorBy($insertType)
	{
		if($insertType == 1)//If Tag, process is Ignored
			return true;
		else if($insertType == 2)//Category
		{
			//d($insertType);
			//Create Author Category, takes and ID and adds to Configuration Table

			$category = new MYCategory();
			$category->name[(int)Configuration::get('PS_LANG_DEFAULT')] = 'Autor';
			$category->link_rewrite[(int)Configuration::get('PS_LANG_DEFAULT')] = 'Autor';
			$category->id_parent = Category::getRootCategory()->id;//Associates a Home Category
			$category->add();

			if(!$category->id)
				return false;
			else
				$category->insertBBMCategory($category->id, 0);

			if(!Configuration::updateValue('BBM_CATEGORY_ID_AUTOR', $category->id))
				return false;

			return true;
		}
		else //3 = No Definition
		{
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
	        'input' => array
	        (
           		array
           		(
				  'type'      => 'radio',                               
				  'label'     => $this->l('Insert Authors as: '),       
				  'desc'      => $this->l('Even when opting for "None", authors will still be associated with ebooks through "customs features", making them possible to be displayed on the product page.'),   
				  'name'      => 'autor_insert_type',
				  'class'     => 't',                             
				  'required'  => true,                                                                  
				  'is_bool'   => false,                                 
			  	  'values'    => array
			  	  (                                 
				    array
				    (
				      'id'    => 'tag',                           
				      'value' => 1,                                
				      'label' => $this->l('Tag')                   
				    ),
				    array
				    (
				      'id'    => 'categoria',
				      'value' => 2,
				      'label' => $this->l('Category')
				    ),
				    array
				    (
				      'id'    => 'nenhum',
				      'value' => 3,
				      'label' => $this->l('None')
				    )
				  )
				)
	        ),
	        'submit' => array
	        (
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
	        'input' => array
	        (
	            array
	            (
	                'type'  	=> 'text',
	                'label' 	=> $this->l('Identification Key'),
	                // 'desc' 	=> $this->l('Operation'),        
	                'name'  	=> 'client_id',
	                'size'  	=> 20,
	                'required'  => true
	            ),
	            array
	            (
	                'type' 		=> 'text',
	                'label' 	=> $this->l('Secret Key'),
	                'name' 		=> 'client_secret',
	                'size' 		=> 20,
	                'required'  => true
	            ),
           		array
           		(
				  'type'      => 'radio',                               
				  'label'     => $this->l('Operation'),        
				  'desc'      => $this->l('Select "complete" to import all our ebooks and "update" to import only those that were updated and / or removed yesterday.'),  
				  'name'      => 'operation',                              
				  'required'  => true,                                  
				  'class'     => 't',                                   
				  'is_bool'   => true,                                  
			  	  'values'    => array
			  	  (                                 
				    array
				    (
				      'id'    => 'complete',                           
				      'value' => 1,                                    
				      'label' => $this->l('Complete')                  
				    ),
				    array
				    (
				      'id'    => 'update',
				      'value' => 2,
				      'label' => $this->l('Update')
				    )
				  ),
				),
				array
				(
				  'type'      => 'radio',                               
				  'label'     => $this->l('Environment'),        
				  //'desc'      => $this->l('Sandbox for testing and Production for real!'), 
				  'name'      => 'environment',                              
				  'required'  => true,                                  
				  'class'     => 't',                                   
				  'is_bool'   => true,                                  
			  	  'values'    => array
			  	  (                                 
				    array
				    (
				      'id'    => 'sandbox',                           
				      'value' => 1,                                   
				      'label' => $this->l('Test')                    
				    ),
				    array
				    (
				      'id'    => 'production',
				      'value' => 2,
				      'label' => $this->l('Production')
				    )
				  ),
				)
	        ),
	        'submit' => array
	        (
	            'title' => $this->l('Import'),
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

	    if($this->clientID)
	    	$helper->fields_value['client_id'] = trim(Configuration::get('BBM_OPTION_CLIENT_ID'));
	    else
	    	$helper->fields_value['client_id'] = '';
	    if($this->clientSecret)
	    	$helper->fields_value['client_secret'] = trim(Configuration::get('BBM_OPTION_CLIENT_SECRET'));
	    else
	    	$helper->fields_value['client_secret'] = '';
    	if($this->operation == 1)
	    	$helper->fields_value['operation'] = 1;
	    else if($this->operation == 2)
	    	$helper->fields_value['operation'] = 2;
	    if($this->environment == 1)
	    	$helper->fields_value['environment'] = 1;
	    else if($this->environment == 2)
	    	$helper->fields_value['environment'] = 2;
		
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
	   
	    if(!Db::getInstance()->Execute($sql))
	    	return false;

	    $sql= "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "bbm_category`
    	(
	    	`id` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	    	`id_category` INT(10) NOT NULL,
	    	`bbm_id_category` VARCHAR(10) NOT NULL
    	)";
	   
	    if(!Db::getInstance()->Execute($sql))
	    	return false;

        return true;
	}

	//Keeps quantity at 0 which is a workaround
	public function hookActionOrderStatusPostUpdate($params)
	{
		foreach($params['cart']->getProducts() as $product)
		{
			if(MYProduct::getIDBBMByID($product['id_product']))
				StockAvailableCore::setQuantity($product['id_product'], $product['id_product_attribute'], 0);
		}
	}

	//Checkout here
	public function hookActionPaymentConfirmation($params)
	{
		$bbmEbooks = array();

		foreach($params['cart']->getProducts() as $product)
		{
			if($idBBM = MYProduct::getIDBBMByID($product['id_product']))
				$bbmEbooks[$idBBM] = $product;
		}

		//d($bbmEbooks);

		if(count($bbmEbooks))
		{
			try
			{
				$purchase = new \BBM\Purchase($this->clientID, $this->clientSecret);

				$purchase->environment = $this->environmentAlias[$this->environment];

				//$purchase->verbose(true);

				$customer = new MYCustomer($params['cart']->id_customer);

				$address = $customer->getMYAddresses((int)Configuration::get('PS_LANG_DEFAULT'), $params['cart']->id_address_delivery);

				$gender = array(1 => 'm', 2 => 'f');

				preg_match_all('!\d+!', $address[0]['postcode'], $zipCode);

				$bbmCustomer = array(
				    'customerIdentificationNumber'  => (int) $customer->id, // INT, YOUR STORE CUSTOMER ID
				    'customerFullname' 				=> $customer->firstname . ' ' . $this->context->customer->lastname, // STRING, CUSTOMER FULL NAME
				    'customerEmail' 				=> $customer->email, // STRING, CUSTOMER EMAIL
				    'customerGender' 				=> $gender[$customer->id_gender], // ENUM, CUSTOMER GENDER, USE m OR f (LOWERCASE!! male or female)
				    'customerBirthday' 				=> str_replace('-', '/', $this->context->customer->birthday), // STRING, CUSTOMER BIRTH DATE, USE Y/m/d (XXXX/XX/XX)
				    'customerCountry' 				=> $address[0]['country_iso'], // STRING, 2 CHAR STRING THAT INDICATE THE CUSTOMER COUNTRY (BR, US, ES, etc)
				    'customerZipcode' 				=> implode('', $zipCode[0]), // STRING, POSTAL CODE, ONLY NUMBERS
				    'customerState'				 	=> $address[0]['state_iso'] // STRING, 2 CHAR STRING THAT INDICATE THE CUSTOMER STATE (RJ, SP, NY, etc)
				);

				$purchase->setCustomer($bbmCustomer);

				foreach ($bbmEbooks as $key => $ebook) 
				{
					$purchase->addItem($key, $ebook['price'], MYProduct::getIsoCodeByIDBBM($key));//Bibliomundi ID and Price
				}

				$purchase->validate();

				$purchase->checkout($params['id_order'], time());

				//exit;
			}
			catch(Exception $e)
			{
				//d($e);
				//Error at this moment is really serious.
			}
		}
	}

	//Validate
	public function hookDisplayBeforePayment($params)
	{
		$bbmEbooks = array();

		foreach($params['cart']->getProducts() as $product)
		{
			if($idBBM = MYProduct::getIDBBMByID($product['id_product']))
			{
				$bbmEbooks[$idBBM] = $product;
			}
		}

		if(count($bbmEbooks))
		{
			try
			{
				$purchase = new \BBM\Purchase($this->clientID, $this->clientSecret);

				$purchase->environment = $this->environmentAlias[$this->environment];

				//$purchase->verbose(true);

				$customer = new MYCustomer($params['cart']->id_customer);

				$address = $customer->getMYAddresses((int)Configuration::get('PS_LANG_DEFAULT'), $params['cart']->id_address_delivery);

				$gender = array(1 => 'm', 2 => 'f');

				preg_match_all('!\d+!', $address[0]['postcode'], $zipCode);

				$bbmCustomer = array(
				    'customerIdentificationNumber'  => (int) $customer->id, // INT, YOUR STORE CUSTOMER ID
				    'customerFullname' 				=> $customer->firstname . ' ' . $this->context->customer->lastname, // STRING, CUSTOMER FULL NAME
				    'customerEmail' 				=> $customer->email, // STRING, CUSTOMER EMAIL
				    'customerGender' 				=> $gender[$customer->id_gender], // ENUM, CUSTOMER GENDER, USE m OR f (LOWERCASE!! male or female)
				    'customerBirthday' 				=> str_replace('-', '/', $this->context->customer->birthday), // STRING, CUSTOMER BIRTH DATE, USE Y/m/d (XXXX/XX/XX)
				    'customerCountry' 				=> $address[0]['country_iso'], // STRING, 2 CHAR STRING THAT INDICATE THE CUSTOMER COUNTRY (BR, US, ES, etc)
				    'customerZipcode' 				=> implode('', $zipCode[0]), // STRING, POSTAL CODE, ONLY NUMBERS
				    'customerState'				 	=> $address[0]['state_iso'] // STRING, 2 CHAR STRING THAT INDICATE THE CUSTOMER STATE (RJ, SP, NY, etc)
				);

				$purchase->setCustomer($bbmCustomer);

				foreach ($bbmEbooks as $key => $ebook) 
				{
					$purchase->addItem($key, $ebook['price'], MYProduct::getIsoCodeByIDBBM($key));//Bibliomundi ID and Price
				}

				$purchase->validate();
			}
			catch(Exception $e)
			{
				$errors = array();
				//Regardless of the error, all ebooks are curretly removed from the basket as the API is not informing what error is the cause.
				foreach ($bbmEbooks as $ebook)
				{
					$errors[] = $ebook['name'];

					//Remove from Shopping Cart

			   		Db::getInstance()->execute('
						DELETE FROM `' . _DB_PREFIX_ . 'cart_product`
						WHERE `id_product` = ' . pSQL((int)$ebook['id_product']) . '
						AND `id_cart` = ' . pSQL((int)$params['cart']->id) . '');
				}

				/*$json = json_decode(str_replace("'", '"', $e->getMessage()));//Temporary Workaround
				$errors = array();

				foreach ($json as $ebookError) 
			    {
			   		//Locates ID which returned the message
			   		preg_match('#ID\s([0-9]+)#', $ebookError->message, $match);
			   		$idEbookBBM = $match[1];

					$errors[] = $bbmEbooks[$idEbookBBM]['name'];

			   		//Remove from Shopping Cart

			   		Db::getInstance()->execute('
						DELETE FROM '._DB_PREFIX_.'cart_product
						WHERE id_product = '.(int)$bbmEbooks[$idEbookBBM]['id_product'].'
						AND id_cart = '.(int)$params['cart']->id. '');
			    }*/

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
		if(isset($this->cartErrorNumber))
		{
			switch($this->cartErrorNumber)
			{
				//It might be required to create na alternative than this simple Delete and Update
				case 1 :
					Db::getInstance()->execute('
						DELETE FROM `' . _DB_PREFIX_ . 'cart_product`
						WHERE `id_product` = ' . pSQL((int)$this->cartParams['product']->id) . '
						AND `id_cart` = ' . pSQL((int)$this->cartParams['cart']->id) . '');
				break;

				case 2 :
					Db::getInstance()->execute('
						UPDATE `' . _DB_PREFIX_ . 'cart_product`
						SET `quantity` = 1, `date_add` = NOW()
						WHERE `id_product` = ' . pSQL((int)$this->cartParams['product']->id) . '
						AND `id_cart` = ' . pSQL((int)$this->cartParams['cart']->id) . '');
				break;
			}

			$this->context->controller->errors[] = Tools::displayError("You can not purchase more than 1 unit of the product \"{$this->cartParams['product']->name}\"", !Tools::getValue('ajax'));
		}
	}

	public function hookActionBeforeCartUpdateQty($params)
	{
		$this->{'cartParams'} = $params;
		$this->{'cartErrorNumber'} = null;
		
		//1- Product is Bibliomundi´s, it is not in the Shopping Cart and its quantity is greater than 1
		//2 - Product is Bibliomundi´s, it is already in the Shopping Cart and operation is addition
		
		if(MYProduct::getIDBBMByID($this->cartParams['product']->id))
		{
			$isInCart = $this->cartParams['cart']->containsProduct($this->cartParams['product']->id);

			//Rule 1
			if(!$isInCart && $this->cartParams['quantity'] > 1)
				$this->cartErrorNumber = 1;
			//Rule 2
			else if($isInCart && $this->cartParams['operator'] == 'up')
				$this->cartErrorNumber = 2;
		}
	}

	public function hookActionDownloadBBMFile($params)
	{
        $download = new \BBM\Download($this->clientID, $this->clientSecret);

        $download->environment = $this->environmentAlias[$this->environment];

        //$download->verbose(true);

        $data = array
        (
            'ebook_id' => (int) $params['id_bbm_product'],
            'transaction_time' => time(),
            'transaction_key' => $params['id_order'] 
        );

        try
        {
            $download->validate($data);
            
            $download->download();
        }
        catch(\BBM\Server\Exception $e)
        {
        	return $e->getMessage();
        }
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

		if(count($categories))
			$category->deleteSelection(array_map(function($category){return $category['id_category'];}, $categories));//Returns multidimentional Array

		if(count($products))
			$product->deleteSelection(array_map(function($product){return $product['id_product'];}, $products));//Ditto

		$feature->deleteSelection
		(
			array
			(
				$this->featureIDISBN, 
				$this->featureIDPublisherName,
				$this->featureIDFormatType,
				$this->featureIDEditionNumber,
				$this->featureIDIdiom,
				$this->featureIDPagesNumber,
				$this->featureIDProtectionType,
				$this->featureIDAgeRating,
				$this->featureIDCollectionTitle,
				$this->featureIDAutor,
				$this->featureIDIlustrador
			)
		);

		Db::getInstance()->delete('configuration',"name LIKE 'BBM_%'");

		Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'bbm_product`');
		
		Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'bbm_category`');

		return true;
	}

	public function writeLog()
	{
		$fp = fopen(dirname(__FILE__) . "/log/{$this->operationAlias[$this->operation]}.txt", 'a');

	    fwrite($fp, date('Y-m-d H:i:s') . ' - ' . $this->msgLog . "\n");

	    fclose($fp);
	}
}
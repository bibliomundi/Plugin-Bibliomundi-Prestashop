<?php
/*
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
*  @author Carlos Magno <cmagnosoares@gmail.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
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
	 * As features serão os campos personalizados do nosso módulo. Nao considere gambiarra, por favor!
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
	public $featureIDAutor;//Os autores tbem serao inseridos como atributos.
	public $featureIDIlustrador;//Os autores tbem serao inseridos como atributos.

   /*
    *
	* O autor será inserido por default como atributo, mas ainda iremos possibilitar ao 
	* Usuário optar por escolher entre inserir os autores como categoria ou como tags 
	*
	*/

	public $categoryIDAutor;//Id da categoria autor que sera o pai para todas os autores inseridos
	

	public function __construct()
	{
		$this->name = 'bibliomundi';
		$this->version = '1.0';
		$this->author = 'Bibliomundi';

		$this->displayName = $this->l('Integração com os ebooks da Bibliomundi');
    	$this->description = $this->l('Distruibuidora de livros digitais.');

    	$this->ps_versions_compliancy = array('min' => '1.6');

    	$this->confirmUninstall = $this->l('Você tem certeza que deseja desinstalar o nosso módulo?');


		$this->bootstrap = true;

		parent::__construct();

		$this->loadFiles();
		
		$this->getConfig();
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

	public function uninstall()
	{
		if (!parent::uninstall() || !$this->deleteFromDB())
	    	return false;
	  
		return true;
	}

	private function setConfig()
	{
		/* Somente estes valores poderão ser atualizados após a instalação */

        Configuration::updateValue('BBM_OPTION_CLIENT_ID', $this->clientID);
        Configuration::updateValue('BBM_OPTION_CLIENT_SECRET', $this->clientSecret);
        Configuration::updateValue('BBM_OPTION_OPERATION', $this->operation);
        Configuration::updateValue('BBM_OPTION_ENVIRONMENT', $this->environment);
	}

	private function getConfig()
	{
		/* Pego a porra toda sempre, mesmo sabendo que em algumas situações o retorno será nulo */

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

			if(!Category::categoryExists($aux))//para o caso do usuario ter deletado a categoria Autor
			{
				$category = new Category();
				$category->name[(int)Configuration::get('PS_LANG_DEFAULT')] = 'Autor';
				$category->link_rewrite[(int)Configuration::get('PS_LANG_DEFAULT')] = 'Autor';
				$category->id_parent = Category::getRootCategory()->id;//Associa a categoria Home
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

	//Coração do módulo!
	public function proccess()
	{
		try
		{
			set_time_limit(0);//Evitar timeout, tendo em vista que iremos realizar diversas

			//header('Content-Type: application/xml; charset=utf-8'); echo $this->getCatalog(); exit;
			$parser = new \BBM\parser\OnixParser($this->getCatalog());

			//d($parser->getOnix()->getProductsAvailable());

			if(!$productsAvailable = $parser->getOnix()->getProductsAvailable())
				throw new Exception("Não há ebooks para importar!");
			
			//Seja complete ou update, está tudo aqui!	
			foreach($productsAvailable as $bbmProduct)
			{
				//d($bbmProduct);
				$product = new MYProduct();

				$product->bbm_id_product = $bbmProduct->getId();

				$product->is_bbm = true;

				$idProductAlreadyInserted = MYProduct::getIDByIDBBM($bbmProduct->getId());//Checa se ja existe

				//Evita que um mesmo ebook seja inserido mais de uma vez
				if($idProductAlreadyInserted && $bbmProduct->getOperationType() == 'insert')
					continue;

				if($idProductAlreadyInserted && $bbmProduct->getOperationType() == 'update')
					$product = new MYProduct($idProductAlreadyInserted);

				//Para o caso do catalog ser o update e a operacao ser Deletar produto
				if($bbmProduct->getOperationType() == 'delete')
				{
					if($idProductAlreadyInserted)
					{
						$product = new MYProduct($idProductAlreadyInserted);
						$product->delete();
						continue;//Passa para o proximo ebook
					}
					else
						continue;
				}

				$product->name[(int)Configuration::get('PS_LANG_DEFAULT')] = $bbmProduct->getTitle();
		        $product->meta_keywords[(int)Configuration::get('PS_LANG_DEFAULT')] = $bbmProduct->getTitle();
		        $product->link_rewrite[(int)Configuration::get('PS_LANG_DEFAULT')] = Tools::link_rewrite($bbmProduct->getTitle());

				$product->price = $bbmProduct->getPrice();
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

				$categoriesIds = array();//Vamos inserindo as categorias para so depois associa-las ao produto
				$tags 		   = $bbmProduct->getTags();//Se nao houver tags um array vazio eh retornado

				//Assuntos(Categorias)
				if(count($bbmProduct->getCategories()))
				{
					foreach ($bbmProduct->getCategories() as $bbmCategory)
					{
						$category = new MYCategory();

						$category->bbm_id_category = $bbmCategory->getCode();

						if($id = MYCategory::getIDByIDBBM($bbmCategory->getCode()))
							$category = new MYCategory($id);
						else //Inserir uma nova
						{
							$category->is_bbm = true;

							$category->name[(int)Configuration::get('PS_LANG_DEFAULT')] = $bbmCategory->getName();
							$category->link_rewrite[(int)Configuration::get('PS_LANG_DEFAULT')] = Tools::link_rewrite($bbmCategory->getName());
							$category->id_parent = Category::getRootCategory()->id;//Associa a categoria default que geralmente eh a home
							$category->add();
						}
						

						$categoriesIds[] = $category->id;
					}
				}
				
				//Segundo regras de negocio, o autor NAO PODE MUDAR, logo, ignoramo-os no update
				if($bbmProduct->getOperationType() == 'insert')
				{
					if(Configuration::get('BBM_AUTOR_INSERT_TYPE') != 3)//3 nao insere como nada
					{
						//Contribuidores
						if(count($bbmProduct->getContributors()))
						{
							foreach ($bbmProduct->getContributors() as $contributor)
							{
								if($contributor instanceof \BBM\model\Contributor\Autor)
								{
									//Vamos inserir o autor como uma categoria, tag ou simplesmente deixá-lo como atributo(feature)

									if(Configuration::get('BBM_AUTOR_INSERT_TYPE') == 1)//Inserir como tag
										$tags[] = $contributor->getFullName();
									else if(Configuration::get('BBM_AUTOR_INSERT_TYPE') == 2)//Inserir como categoria
									{
										$category = new MYCategory();

										$category->bbm_id_category = $contributor->getId();

										if($id = MYCategory::getIDByIDBBM($contributor->getId()))
											$category = new MYCategory($id);
										else //Inserir uma nova
										{
											$category->is_bbm = true;

											$category->name[(int)Configuration::get('PS_LANG_DEFAULT')] = $contributor->getFullName();
											$category->link_rewrite[(int)Configuration::get('PS_LANG_DEFAULT')] = Tools::link_rewrite($contributor->getFullName());
											$category->id_parent = $this->categoryIDAutor;//Associa a categoria padrao que criamos que se chama Autor
											$category->add();
										}

										$categoriesIds[] = $category->id;
									}
								}
								/*else if($contributor instanceof \BBM\model\Contributor\Ilustrador)
								{
									Por hora, nao ha nao pra fazer aqui!
								}*/
							}
						}
					}
				}

				$product->id_category_default = $categoriesIds[0];//Nao ha regras aqui. A escolha pode ser aleatoria

				if($idProductAlreadyInserted && $bbmProduct->getOperationType() == 'update')
				{
					$product->deleteFeatures();//Eh assim que o prestashop trabalha internamente. Deleta todas as features antes e depois cria novamente.
					$product->update();
				}
				else
					$product->add();//Adicionar antes para poder ter acesso ao insert_id e entao poder realizar as operacoes abaixo

				//Associa todas as tags, inclusive o autor, se for o caso, ao produto.
				//Obs. Se ja existir uma tag com o mesmo nome, o prestashop ignora a inserção. Excelente parao caso de ser update. Nao temos que fazer nada.
				if(count($tags))
					Tag::addTags((int)Configuration::get('PS_LANG_DEFAULT'), $product->id, $tags);

				//Associa o produto a todas as categorias criadas. Se ja estiver, a associacao eh ignorada!
				$product->addToCategories($categoriesIds);

				//Regras de inserção e associação das custom features

				//1 - Criar a feature e pegar o id dela(Aqui cria uma vez so. Ao iniciarmos a classe ja temos que ter ela)
				//2 - Criar o feature value atraves da funcao addFeaturesToDB e pegar o id
				//3 - Associar o feature value ao produto e a linguagem atraves da funcao addFeaturesCustomToDB

				$idFeatureValue = $product->addFeaturesToDB($this->featureIDISBN, null, 1);
				$product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getISBN());

				$idFeatureValue = $product->addFeaturesToDB($this->featureIDIdiom, null, 1);
				$product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getIdiom());

				if(!empty($bbmProduct->getCollectionTitle()))
				{
					$idFeatureValue = $product->addFeaturesToDB($this->featureIDCollectionTitle, null, 1);
					$product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getCollectionTitle());
				}

				$idFeatureValue = $product->addFeaturesToDB($this->featureIDAgeRating, null, 1);
				$product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getAgeRating());

				$idFeatureValue = $product->addFeaturesToDB($this->featureIDProtectionType, null, 1);
				$product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getProtectionType());

				$idFeatureValue = $product->addFeaturesToDB($this->featureIDPagesNumber, null, 1);
				$product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getPagesNumber());

				$idFeatureValue = $product->addFeaturesToDB($this->featureIDEditionNumber, null, 1);
				$product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getEditionNumber());

				$idFeatureValue = $product->addFeaturesToDB($this->featureIDFormatType, null, 1);
				$product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getFormatType());

				$idFeatureValue = $product->addFeaturesToDB($this->featureIDPublisherName, null, 1);
				$product->addFeaturesCustomToDB($idFeatureValue, 1, $bbmProduct->getPublisherName());

				//Insere os autores separando-os por virgula
				if($autorsName = implode(',', array_map(array('\BBM\model\Contributor', 'getFullNameStatically'), $bbmProduct->getContributorsByType('Autor'))))
				{
					$idFeatureValueAutor = $product->addFeaturesToDB($this->featureIDAutor, null, 1);
					$product->addFeaturesCustomToDB($idFeatureValueAutor, 1, $autorsName);
				}

				//Insere os ilustradores separando-os por virgula
				if($ilustradorsName = implode(',', array_map(array('\BBM\model\Contributor', 'getFullNameStatically'), $bbmProduct->getContributorsByType('Ilustrador'))))
				{
					$idFeatureValueIlustrador = $product->addFeaturesToDB($this->featureIDIlustrador, null, 1);
					$product->addFeaturesCustomToDB($idFeatureValueIlustrador, 1, $ilustradorsName);
				}

				/*
				 * Download
				 */						

				if($bbmProduct->getOperationType() == 'insert')
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

				//Para o caso do update, pois so podemos ter uma imagem como capa
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
		}
		catch(Exception $e)
		{
			throw $e;
		}
	}

	public function getContent()
	{
		$output = '';

		//Quando o formulário de definição inicial for submetido(Por hora somente a inserção do autor mesmo)
		if(Tools::isSubmit('submit' . $this->name . 'autor_insert_type'))
		{
			if(in_array(Tools::getValue('autor_insert_type'), array('1', '2', '3')))//0 = Sem definição, 1 = Categoria, 2 = Tag
			{
				if(!$this->insertAutorBy(Tools::getValue('autor_insert_type')))
					$output .= $this->displayError($this->l('Erro interno!'));
				else
					Configuration::updateValue('BBM_AUTOR_INSERT_TYPE', Tools::getValue('autor_insert_type'));
			}
			else
				$output .= $this->displayError($this->l('Tipo de autor inválido!'));

		}
		else if (Tools::isSubmit('submit' . $this->name . 'operation'))
	    {
	        if (empty(Tools::getValue('client_id')) || empty(Tools::getValue('client_secret')))
	            $output .= $this->displayError($this->l('Chave de identificação ou Chave Secreta não preencidos!'));
	        else if(!in_array(Tools::getValue('operation'), array('1', '2')) || !in_array(Tools::getValue('environment'), array('1', '2')))
	        	$output .= $this->displayError($this->l('Tipo de Operação ou ambiente inválidos!'));
	        else
	        {
	        	$this->clientID       = strval(Tools::getValue('client_id'));
		        $this->clientSecret   = strval(Tools::getValue('client_secret'));
		        $this->operation 	  = strval(Tools::getValue('operation'));
		        $this->environment    = strval(Tools::getValue('environment'));

	        	try
	        	{
 					$this->proccess();

 					$output .= $this->displayConfirmation($this->l('Operação realizada com sucesso!'));
	        	}
	        	catch(Exception $e)
	        	{
	        		$this->{'msgLog'} = $e->getMessage();
	        		$output .= $this->displayError($this->l($e->getMessage()));
	        	}
	        	
        		$this->writeLog();
        		$this->setConfig();//Atualiza os dados de configuração independentemente
	        }
	    }

	    return $output .= $this->displayForm();
	}

	public function displayForm()
	{	    
	    //Se a forma(Como Tag ou Categoria) de inserir o autor ainda não foi definida
	    if(Configuration::get('BBM_AUTOR_INSERT_TYPE') === false)
		{
			return $this->getFormInsertAutor();
		}
		else
		{
		    return $this->getFormConfiguration();
	    }
	}

	//As features serao os campos personalizados, digamos assim.
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
	    		//Associa o id a feature
	    		if(!Configuration::updateValue('BBM_FEATURE_ID_' . $key, $feature->id))
	    			return false;
	    	}
 		}

		//Seta uns valores default para essas opcoes
 		if(!Configuration::updateValue('BBM_OPTION_CLIENT_ID', null) ||
        	!Configuration::updateValue('BBM_OPTION_CLIENT_SECRET', null) ||
        		!Configuration::updateValue('BBM_OPTION_OPERATION', 1) ||
        			!Configuration::updateValue('BBM_OPTION_ENVIRONMENT', 1))
 			return false;

 		return true;
	}

	//As categorias serão criadas dinamicamente, pois seria inviável inserí-las previamente no banco(Diferentemente
	//das features), tendo em vista que são inúmeras. Após cria-las iremos inserir na tabela de configuração.
	//Caso algum outro ebook pertenca a ela, simplesmente pegamos o id e associamos
	private function insertAutorBy($insertType)
	{
		if($insertType == 1)//Quando for tag nao precisamos fazer nada
			return true;
		else if($insertType == 2)//Categoria
		{
			//d($insertType);
			//Criar a categoria autor, pegar o id e inserir na tabela configuracao

			$category = new MYCategory();
			$category->is_bbm = true;
			$category->name[(int)Configuration::get('PS_LANG_DEFAULT')] = 'Autor';
			$category->link_rewrite[(int)Configuration::get('PS_LANG_DEFAULT')] = 'Autor';
			$category->id_parent = Category::getRootCategory()->id;//Associa a categoria Home
			$category->add();

			if(!$category->id)
				return false;

			if(!Configuration::updateValue('BBM_CATEGORY_ID_AUTOR', $category->id))
				return false;

			return true;
		}else //3 = Sem definição
		{
			// Não há o que fazer aqui. Simplesmente nao iremos inserir nem como categoria, nem como tag. O autor
			// será apenas um atributo.

			return true;
		}
	}

	private function getFormInsertAutor()
	{
		// Get default language
	    $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

		$fields_form[0]['form'] = array(
	        // 'legend' => array(
	        //     'title' => $this->l('Tipo'), 
	        // ),
	        'input' => array
	        (
           		array
           		(
				  'type'      => 'radio',                               
				  'label'     => $this->l('Inserir Autores como: '),       
				  'desc'      => $this->l('Mesmo ao optar por "Nenhum" ainda sim os Autores serão associados, aos ebooks, através das "customs features", tornando-os possíveis de serem exibidos na página do produto.'),   
				  'name'      => 'autor_insert_type',                             
				  'required'  => true,                                  
				  'class'     => 't',                                   
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
				      'label' => $this->l('Categoria')
				    ),
				    array
				    (
				      'id'    => 'nenhum',
				      'value' => 3,
				      'label' => $this->l('Nenhum')
				    )
				  )
				)
	        ),
	        'submit' => array
	        (
	            'title' => $this->l('Prosseguir'),
	            'class' => 'button'
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
		            'desc' => $this->l('Prosseguir'),
		            'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
		            '&token='.Tools::getAdminTokenLite('AdminModules'),
		        ),
		        'back' => array(
		            'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
		            'desc' => $this->l('Back to list')
		        )
	    );

	    $helper->fields_value['autor_insert_type'] = 1;//Tag como default

	    $html = '<h2>Informe como deseja que os autores dos nossos ebooks sejam exibidos em sua loja</h2>';

	    return $html . $helper->generateForm($fields_form);
	}

	private function getFormConfiguration()
	{
		// Get default language
	    $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

		// Init Fields form array
	    $fields_form[0]['form'] = array(
	        'legend' => array(
	            'title' => $this->l('Configurações'),
	        ),
	        'input' => array
	        (
	            array
	            (
	                'type'  	=> 'text',
	                'label' 	=> $this->l('Chave de Identificação'),
	                //'desc' 	=> $this->l('Operação'),        
	                'name'  	=> 'client_id',
	                'size'  	=> 20,
	                'required'  => true
	            ),
	            array
	            (
	                'type' 		=> 'text',
	                'label' 	=> $this->l('Chave Secreta'),
	                'name' 		=> 'client_secret',
	                'size' 		=> 20,
	                'required'  => true
	            ),
           		array
           		(
				  'type'      => 'radio',                               
				  'label'     => $this->l('Operação'),        
				  'desc'      => $this->l('Selecione "complete" para importar todos os nossos ebooks e "update" para importar somente os que foram atualizados e/ou removidos ontem.'),  
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
				  'label'     => $this->l('Ambiente'),        
				  //'desc'      => $this->l('Sandbox para teste e Production pra valer!'), 
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
				      'label' => $this->l('Teste')                    
				    ),
				    array
				    (
				      'id'    => 'production',
				      'value' => 2,
				      'label' => $this->l('Produção')
				    )
				  ),
				)
	        ),
	        'submit' => array
	        (
	            'title' => $this->l('Importar'),
	            'class' => 'button'
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
	     
	    // Carrega/seta os valores do formulário, se já existirem.

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

	    $html = '<h2>Atenção! A importação pode demorar vários minutos</h2>';

	    return $html . $helper->generateForm($fields_form);
	}

	//Adicionar campos para identificar que o produto é nosso
	private function createCustomFieldsToDB()
	{
		$sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'product ADD bbm_id_product VARCHAR(10) NULL, ADD is_bbm TINYINT(1) NULL';

		if(!Db::getInstance()->Execute($sql))
        	return false;

        $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'category ADD bbm_id_category VARCHAR(10) NULL, ADD is_bbm TINYINT(1) NULL';

		if(!Db::getInstance()->Execute($sql))
        	return false;

        return true;
	}

	//Mantem a quantidade sempre em 0 que é o símbolo do infinito ):
	public function hookActionOrderStatusPostUpdate($params)
	{
		foreach($params['cart']->getProducts() as $product)
		{
			if(MYProduct::getIDBBMByID($product['id_product']))
				StockAvailableCore::setQuantity($product['id_product'], $product['id_product_attribute'], 0);
		}
	}

	//Realizar o checkout aqui
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
					$purchase->addItem($key, $ebook['price']);//Id bbm e preço
				}

				$purchase->validate();

				$purchase->checkout($params['id_order'], time());

				//exit;
			}
			catch(Exception $e)
			{
				//d($e);
				//Um erro aqui eh grave.
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
					$purchase->addItem($key, $ebook['price']);//Id bbm e preço
				}

				$purchase->validate();
			}
			catch(Exception $e)
			{
				//Independente do erro removemos todos os ebooks, pois a api não está retornando qual o erro que ocorreu
				foreach ($bbmEbooks as $ebook)
				{
					$errors[] = $ebook['name'];

					//Remover do carrinho

			   		Db::getInstance()->execute('
						DELETE FROM '._DB_PREFIX_.'cart_product
						WHERE id_product = '.(int)$ebook['id_product'].'
						AND id_cart = '.(int)$params['cart']->id. '');
				}
/*
				$json = json_decode(str_replace("'", '"', $e->getMessage()));//gambiarra momentanea
				$errors = array();

				foreach ($json as $ebookError) 
			    {
			   		//Encontra id que foi retornado da mensagem
			   		preg_match('#ID\s([0-9]+)#', $ebookError->message, $match);
			   		$idEbookBBM = $match[1];

					$errors[] = $bbmEbooks[$idEbookBBM]['name'];

			   		//Remover do carrinho

			   		Db::getInstance()->execute('
						DELETE FROM '._DB_PREFIX_.'cart_product
						WHERE id_product = '.(int)$bbmEbooks[$idEbookBBM]['id_product'].'
						AND id_cart = '.(int)$params['cart']->id. '');
			    }*/

				$this->context->controller->errors[] = Tools::displayError('Ocorreu um problema interno com o(s) seguinte(s) ebooks: ' . implode(',', $errors) . '. Removemos do carrinho pra você. Desculpe-nos pelo transtorno!', !Tools::getValue('ajax'));
			}
		}
	}

/**
 * Esse dois hooks, abaixo, juntos sao necessarios para 
 * 
 * No hook ActionBeforeCartUpdateQty eu consigo saber se foi adicao ou subtracao do produto, se o mesmo ja esta o carrinho
 * No hook ActionCartSave, que eh executado depois da adicao ou subtracao, eu deleto ou atualizo o produto
 * 
 */
	public function hookActionCartSave()
	{
		if(isset($this->cartErrorNumber))
		{
			switch($this->cartErrorNumber)
			{
				//Talvez seja necessario algo mais que simplesmente fazer estes simples delete e update
				case 1 :
					Db::getInstance()->execute('
						DELETE FROM '._DB_PREFIX_.'cart_product
						WHERE id_product = '.(int)$this->cartParams['product']->id.'
						AND id_cart = '.(int)$this->cartParams['cart']->id. '');
				break;

				case 2 :
					Db::getInstance()->execute('
						UPDATE '._DB_PREFIX_.'cart_product
						SET quantity = 1, date_add = NOW()
						WHERE id_product = '.(int)$this->cartParams['product']->id.'
						AND id_cart = '.(int)$this->cartParams['cart']->id. '');
				break;
			}

			$this->context->controller->errors[] = Tools::displayError("Você não pode comprar mais de 1 unidade do produto \"{$this->cartParams['product']->name}\"", !Tools::getValue('ajax'));
		}
	}

	public function hookActionBeforeCartUpdateQty($params)
	{
		$this->{'cartParams'} = $params;
		$this->{'cartErrorNumber'} = null;
		
		//1 - O produto eh nosso, nao esta no carrinho e quantidade adicionada eh maior que 1
		//2 - O produto eh nosso, já esta no carrinho e a operacao eh adição
		
		if($bbmIdProduct = MYProduct::getIDBBMByID($this->cartParams['product']->id))
		{
			$isInCart = $this->cartParams['cart']->containsProduct($this->cartParams['product']->id);

			//Regra 1
			if(!$isInCart && $this->cartParams['quantity'] > 1)
				$this->cartErrorNumber = 1;
			//Regra 2
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
		require_once dirname(__FILE__) . '/lib/bbm-onix-parser/OnixParser.php';

		return true;
	}

	//Remove tudo que é nosso da base dados
	private function deleteFromDB()
	{
		$categories = Db::getInstance()->executeS('SELECT id_category FROM ' . _DB_PREFIX_ . 'category WHERE is_bbm IS NOT NULL');					
		$products   = Db::getInstance()->executeS('SELECT id_product  FROM ' . _DB_PREFIX_ . 'product  WHERE is_bbm IS NOT NULL');//Os produtos se encarregam de deletar as tags

		$category = new Category();
		$product  = new Product();
		$feature  = new Feature();

		if(count($categories))
			$category->deleteSelection(array_column($categories,'id_category'));//Eh retornado um array multidimensonal, portanto essa bizarrice

		if(count($products))
			$product->deleteSelection(array_column($products,'id_product'));//Idem

		$feature->deleteSelection(
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

		Db::getInstance()->execute('ALTER TABLE ' . _DB_PREFIX_ . 'product DROP COLUMN bbm_id_product, DROP COLUMN is_bbm');
		
		Db::getInstance()->execute('ALTER TABLE ' . _DB_PREFIX_ . 'category DROP COLUMN bbm_id_category, DROP COLUMN is_bbm');

		return true;
	}

	public function writeLog()
	{
		$fp = fopen(dirname(__FILE__) . "/log/{$this->operationAlias[$this->operation]}.txt", 'a');

	    fwrite($fp, date('Y-m-d H:i:s') . ' - ' . $this->msgLog . "\n");

	    fclose($fp);
	}
	
}


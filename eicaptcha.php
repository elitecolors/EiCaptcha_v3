<?php

/**
 * 2007-2014 PrestaShop
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
 */
if (!defined('_PS_VERSION_'))
	exit;

class EiCaptcha extends Module
{

	private $_html = '';

	public function __construct()
	{
		$this->author = 'eicaptcha';
		$this->name = 'eicaptcha';
		$this->tab = 'front_office_features';
		$this->version = '1.0.0';
		$this->need_instance = 1;
		
		$this->bootstrap = true;
		parent::__construct();

		$this->displayName = $this->l('(AM) eicaptcha');
		$this->description = $this->l('Add a captcha to your website form');

		if ($this->active && (!Configuration::get('CAPTCHA_PUBLIC_KEY') || !Configuration::get('CAPTCHA_PRIVATE_KEY') ))
			$this->warning = $this->l('Captcha Module need to be configurated');
	}

	public function install()
	{
		if (!parent::install() || !$this->registerHook('header') || !$this->registerHook('displayCustomerAccountForm') || !Configuration::updateValue('CAPTCHA_ENABLE_ACCOUNT', 0))
			return false;

		return true;
	}

	public function uninstall()
	{
		if (!parent::uninstall())
			return false;

		if (!Configuration::deleteByName('CAPTCHA_PUBLIC_KEY') || !Configuration::deleteByName('CAPTCHA_PRIVATE_KEY') || !Configuration::deleteByName('CAPTCHA_ENABLE_ACCOUNT'))
			return false;

		return true;
	}

	/**
	 * Soumission de la configuration dans l'admin
	 */
	public function postProcess()
	{
		if (Tools::isSubmit('SubmitCaptchaConfiguration'))
		{		
			Configuration::updateValue('CAPTCHA_PUBLIC_KEY', Tools::getValue('CAPTCHA_PUBLIC_KEY'));
			Configuration::updateValue('CAPTCHA_PRIVATE_KEY', Tools::getValue('CAPTCHA_PRIVATE_KEY'));
			Configuration::updateValue('CAPTCHA_ENABLE_ACCOUNT', (int) Tools::getValue('CAPTCHA_ENABLE_ACCOUNT'));
			
			$this->_html .= $this->displayConfirmation($this->l('Settings updated'));
		}
	}

	public function getContent()
	{
		$this->_html .=$this->postProcess();
		$this->_html .= $this->renderForm();
		
		return $this->_html;
	}

	/**
	 * Affichage du formulaire de configuration Admin
	 */
	public function renderForm(){
		
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Eicaptcha Configuration'),
					'icon' => 'icon-cogs'
				),
				'description' => $this->l('To get your own public and private keys please click on the folowing link').'<br /><a href="https://www.google.com/recaptcha/intro/index.html" target="_blank">https://www.google.com/recaptcha/intro/index.html</a>',
				'input' => array(
					array(
						'type' => 'text',
						'label' => $this->l('Captcha private key'),
						'name' => 'CAPTCHA_PRIVATE_KEY',
						'required' => true,
						'empty_message' => $this->l('Please fill the captcha private key'),
					),
					array(
						'type' => 'text',
						'label' => $this->l('Captcha public key'),
						'name' => 'CAPTCHA_PUBLIC_KEY',
						'required' => true,
						'empty_message' => $this->l('Please fill the captcha public key'),
					),
					array(
						'type' => 'radio',
						'label' => $this->l('Enable Captcha for account creation'),
						'name' => 'CAPTCHA_ENABLE_ACCOUNT',
						'required' => true,
						'class' => 't',
						'is_bool' => true,
						'values' => array(
							array(
								'id' => 'active_on',
								'value'=> 1,
								'label'=> $this->l('Enabled'),
							),
							array(
								'id' => 'active_off',
								'value'=> 0,
								'label'=> $this->l('Disabled'),
							),
						),
					),
				),
				'submit' => array(
					'title' => $this->l('Save'),
					'class' => 'button btn btn-default pull-right',
				)
			),
			);
		
		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table =  $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();
		$helper->id = (int)Tools::getValue('id_carrier');
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'SubmitCaptchaConfiguration';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);
		
		return $helper->generateForm(array($fields_form));
		
	}
	
	public function getConfigFieldsValues()
	{
		return array(
			'CAPTCHA_PRIVATE_KEY' => Tools::getValue('CAPTCHA_PRIVATE_KEY', Configuration::get('CAPTCHA_PRIVATE_KEY')),
			'CAPTCHA_PUBLIC_KEY' => Tools::getValue('CAPTCHA_PUBLIC_KEY', Configuration::get('CAPTCHA_PUBLIC_KEY')),
			'CAPTCHA_ENABLE_ACCOUNT' => Tools::getValue('CAPTCHA_ENABLE_ACCOUNT', Configuration::get('CAPTCHA_ENABLE_ACCOUNT')),
		);
	}
	
	/**
	 * Hook Header pour le formulaire de contact
	 */
	public function hookHeader($params)
	{
		//Affichage sur le formulaire de contact
		if ($this->context->controller instanceof ContactController)
			return $this->displayCaptchaContactForm();
			
		if ( $this->context->controller instanceof ProductController ) {	
			$html = '<script type="text/javascript"> 
						var checkCaptchaUrl ="'._MODULE_DIR_.$this->name.'/eicaptcha-ajax.php";
						var RecaptachKey = "'.Configuration::get('CAPTCHA_PUBLIC_KEY').'";
					</script>
					<script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer></script>
					<script type="text/javascript" src="'.$this->_path.'/js/eicaptcha-modules.js"></script>';
			
			return $html;
		}
	}

	/**
	 * Actions ajax du module
	 * (Affichage du message d'erreur en utilisant un template)
	 */
	public function hookAjaxCall()
	{
		$action = Tools::getValue('action');

		if ($action == 'display_captcha_error')
		{
			$this->context->smarty->assign('errors', array($this->l('Please validate the captcha field before submitting your request')));
			$error_block = trim(preg_replace("#\n#", '', $this->context->smarty->fetch(_PS_THEME_DIR_.'errors.tpl')));
			echo $error_block;
		}
	}

	/**
	 * Rajout des champs au formulaire de création de compte
	 */
	public function hookDisplayCustomerAccountForm($params)
	{
		if (Configuration::get('CAPTCHA_ENABLE_ACCOUNT') == 1)
		{
			//Récupération de la clé publique
			$publickey = Configuration::get('CAPTCHA_PUBLIC_KEY');

			if (_PS_VERSION_ > '1.6')
			{
				$error_selector = '.alert';
				$form_selector = '#account-creation_form';
				$prestashop_version = '16';
			}
			else
			{
				$this->context->controller->addCSS($this->_path.'/css/eicaptcha.css');
				$error_selector = '.error';
				$form_selector = '#account-creation_form';
				$prestashop_version = '15';
			}
			$this->context->controller->registerJavascript('modules-eicaptcha', 'modules/'.$this->name.'/js/eicaptcha.js', ['position' => 'bottom', 'priority' => 150]);
			//Ajout du Js
			//$this->context->controller->addJS($this->_path.'/js/eicaptcha.js');

			//Assignation des variables nécessaires au bon fonctionnement du module
			$this->context->smarty->assign('publicKey', $publickey);
			$this->context->smarty->assign('waiting_message', $this->l('Please wait during captcha check'));
			$this->context->smarty->assign('checkCaptchaUrl', _MODULE_DIR_.$this->name.'/eicaptcha-ajax.php');
			$this->context->smarty->assign('errorSelector', $error_selector);
			$this->context->smarty->assign('formSelector', $form_selector);
			$this->context->smarty->assign('prestashopVersion', $prestashop_version);
			
			return $this->display(__FILE__, 'hookDisplayCustomerAccountForm.tpl');
			
		}
	}

	/**
	 * Affichage du captcha sur la page du formulaire de contact
	 */
	private function displayCaptchaContactForm()
	{
		//Gestion des classes en fonction de la version de prestashop
		if (_PS_VERSION_ > '1.6')
		{
			$error_class = 'alert';
			$form_class = 'contact-form-box';
		}
		else
		{
			$error_class = 'error';
			$form_class = 'std';
		}

		$this->context->controller->registerJavascript('modules-eicaptcha', 'modules/'.$this->name.'/js/captcha_contact.js', ['position' => 'bottom', 'priority' => 150]);

		$this->context->smarty->assign('publicKey', Configuration::get('CAPTCHA_PUBLIC_KEY'));
		$this->context->smarty->assign('checkCaptchaUrl', _MODULE_DIR_.$this->name.'/eicaptcha-ajax.php');
		
		return $this->display(__FILE__, 'variable_js.tpl');

	}
}

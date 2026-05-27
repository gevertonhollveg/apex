<?php
/**
 * WebEngine CMS
 * https://webenginecms.org/
 * 
 * @version 1.2.5
 * @author Lautaro Angelico <http://lautaroangelico.com/>
 * @copyright (c) 2013-2023 Lautaro Angelico, All Rights Reserved
 * 
 * Licensed under the MIT license
 * http://opensource.org/licenses/MIT
 */

if(isLoggedIn()) {
	if(isset($_POST['webengineRegister_ajax']) && $_POST['webengineRegister_ajax'] == '1') {
		while(ob_get_level()) ob_end_clean();
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('success' => false, 'message' => lang('error_4',true), 'tab' => 'register'));
		die();
	}
	redirect();
}

$isAjaxRegister = (
	(isset($_POST['webengineRegister_ajax']) && $_POST['webengineRegister_ajax'] == '1') ||
	(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
);

if(!function_exists('registerJsonResponse')) {
	function registerJsonResponse($success, $message, $extra = array()) {
		$response = array_merge(array(
			'success' => (bool)$success,
			'message' => (string)$message,
		), $extra);
		while(ob_get_level()) ob_end_clean();
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($response);
		die();
	}
}

	if(!$isAjaxRegister) {
		echo '<div class="page-title"><span>'.lang('module_titles_txt_1',true).'</span></div>';
	}

try {
	
	if(!mconfig('active')) throw new Exception(lang('error_17',true));
	
	// Register Process
	if(isset($_POST['webengineRegister_submit'])) {
		try {
			$Account = new Account();
			
			if(mconfig('register_enable_recaptcha')) {
				if(!@include_once(__PATH_CLASSES__ . 'recaptcha/autoload.php')) throw new Exception(lang('error_60'));
				$recaptcha = new \ReCaptcha\ReCaptcha(mconfig('register_recaptcha_secret_key'));
				
				$resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
				if(!$resp->isSuccess()) {
					# recaptcha failed
					$errors = $resp->getErrorCodes();
					throw new Exception(lang('error_18',true));
				}
			}
			
			$result = $Account->registerAccount(
				$_POST['webengineRegister_user'],
				$_POST['webengineRegister_pwd'],
				$_POST['webengineRegister_pwdc'],
				$_POST['webengineRegister_email'],
				$isAjaxRegister
			);

			if($isAjaxRegister) {
				$successMessage = lang('success_1',true);
				if(is_array($result) && isset($result['message'])) {
					$successMessage = $result['message'];
				}
				registerJsonResponse(true, $successMessage, array('tab' => 'register', 'reset' => true));
			}
			
		} catch (Exception $ex) {
			if($isAjaxRegister) {
				registerJsonResponse(false, $ex->getMessage(), array('tab' => 'register'));
			}
			message('error', $ex->getMessage());
		}
	}

	if($isAjaxRegister) {
		registerJsonResponse(false, lang('error_4',true), array('tab' => 'register'));
	}
	
	echo '<div class="col-xs-8 col-xs-offset-2" style="margin-top:30px;">';
		echo '<form class="form-horizontal" action="" method="post">';
			echo '<div class="form-group">';
				echo '<label for="webengineRegistration1" class="col-sm-4 control-label">'.lang('register_txt_1',true).'</label>';
				echo '<div class="col-sm-8">';
					echo '<input type="text" class="form-control" id="webengineRegistration1" name="webengineRegister_user" required>';
					echo '<span id="helpBlock" class="help-block">'.langf('register_txt_6', array(config('username_min_len', true), config('username_max_len', true))).'</span>';
				echo '</div>';
			echo '</div>';
			echo '<div class="form-group">';
				echo '<label for="webengineRegistration2" class="col-sm-4 control-label">'.lang('register_txt_2',true).'</label>';
				echo '<div class="col-sm-8">';
					echo '<input type="password" class="form-control" id="webengineRegistration2" name="webengineRegister_pwd" required>';
					echo '<span id="helpBlock" class="help-block">'.langf('register_txt_7', array(config('password_min_len', true), config('password_max_len', true))).'</span>';
				echo '</div>';
			echo '</div>';
			echo '<div class="form-group">';
				echo '<label for="webengineRegistration3" class="col-sm-4 control-label">'.lang('register_txt_3',true).'</label>';
				echo '<div class="col-sm-8">';
					echo '<input type="password" class="form-control" id="webengineRegistration3" name="webengineRegister_pwdc" required>';
					echo '<span id="helpBlock" class="help-block">'.lang('register_txt_8',true).'</span>';
				echo '</div>';
			echo '</div>';
			echo '<div class="form-group">';
				echo '<label for="webengineRegistration4" class="col-sm-4 control-label">'.lang('register_txt_4',true).'</label>';
				echo '<div class="col-sm-8">';
					echo '<input type="text" class="form-control" id="webengineRegistration4" name="webengineRegister_email" required>';
					echo '<span id="helpBlock" class="help-block">'.lang('register_txt_9',true).'</span>';
				echo '</div>';
			echo '</div>';
			
			if(mconfig('register_enable_recaptcha')) {
				# recaptcha v2
				echo '<div class="form-group">';
					echo '<div class="col-sm-offset-4 col-sm-8">';
						echo '<div class="g-recaptcha" data-sitekey="'.mconfig('register_recaptcha_site_key').'"></div>';
					echo '</div>';
				echo '</div>';
				echo '<script src=\'https://www.google.com/recaptcha/api.js\'></script>';
			}
			
			echo '<div class="form-group">';
				echo '<div class="col-sm-offset-4 col-sm-8">';
					echo langf('register_txt_10', array(__BASE_URL__.'tos'));
				echo '</div>';
			echo '</div>';
			echo '<div class="form-group">';
				echo '<div class="col-sm-offset-4 col-sm-8">';
					echo '<button type="submit" name="webengineRegister_submit" value="submit" class="btn btn-primary">'.lang('register_txt_5',true).'</button>';
				echo '</div>';
			echo '</div>';
		echo '</form>';
	echo '</div>';

} catch(Exception $ex) {
	if($isAjaxRegister) {
		registerJsonResponse(false, $ex->getMessage(), array('tab' => 'register'));
	}
	message('error', $ex->getMessage());
}
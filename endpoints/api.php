<?php
/**
 * Main API interface between JavaScript ajax calls and PHP functions.
 * Accepts JSON, POST data or simple GET requests, and returns JSON data.
 *
 * @author Ian Moore (imoore76 at yahoo dot com)
 * @copyright Copyright (C) 2010-2015 Ian Moore (imoore76 at yahoo dot com)
 * @version $Id: api.php 596 2015-04-19 11:50:53Z imoore76 $
 * @package phpVirtualBox
 * @see vboxconnector
 * @see vboxAjaxRequest
 *
 * @global array $GLOBALS['response'] resopnse data sent back via json
 * @name $response
*/

# Turn off PHP errors
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_WARNING);


//Set no caching
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache");

require_once(dirname(__FILE__).'/lib/config.php');
require_once(dirname(__FILE__).'/lib/utils.php');
require_once(dirname(__FILE__).'/lib/vboxconnector.php');
require_once(dirname(__FILE__).'/../classes/IpHelper.php');

// Init session
global $_SESSION;

/*
 * Clean request
 */
$request = clean_request();


global $response;
$response = array('data'=>array('responseData'=>array()),'errors'=>array(),'persist'=>array(),'messages'=>array());

/*
 * Built-in requests
 */
$vbox = null; // May be set during request handling

/**
 * Main try / catch. Logic dictated by incoming 'fn' request
 * parameter.
 */
try {

	/* Check for password recovery file */
	if(file_exists(dirname(dirname(__FILE__)).'/recovery.php')) {
		throw new Exception('recovery.php exists in phpVirtualBox\'s folder. This is a security hazard. phpVirtualBox will not run until recovery.php has been renamed to a file name that does not end in .php such as <b>recovery.php-disabled</b>.',vboxconnector::PHPVB_ERRNO_FATAL);
	}

	/* Check for PHP version */
	if (!version_compare(PHP_VERSION, '5.2.0', '>=')) {
		throw new Exception('phpVirtualBox requires PHP >= 5.2.0, but this server is running version '. PHP_VERSION .'. Please upgrade PHP.');
	}

	# Only valid function chars
	$request['fn'] = preg_replace('[^a-zA-Z0-9_-]', '', $request['fn']);

	/* Check for function called */
	switch($request['fn']) {

		/*
		 * No method called
		 */
	    case '':
	    	throw new Exception('No method called.');
	    	break;

		/*
		 * Return phpVirtualBox's configuration data
		 */
		case 'getConfig':

			$settings = new phpVBoxConfigClass();
			$response['data']['responseData'] = get_object_vars($settings);
			$response['data']['responseData']['host'] = parse_url($response['data']['responseData']['location']);
			$response['data']['responseData']['host'] = $response['data']['responseData']['host']['host'];
			$response['data']['responseData']['phpvboxver'] = @constant('PHPVBOX_VER');

			// Session
			session_init();

			// Hide credentials
			unset($response['data']['responseData']['username']);
			unset($response['data']['responseData']['password']);
			foreach($response['data']['responseData']['servers'] as $k => $v)
				$response['data']['responseData']['servers'][$k] = array('name'=>$v['name']);

			// Vbox version
			$vbox = new vboxconnector();
			$response['data']['responseData']['version'] = $vbox->getVersion();
			$response['data']['responseData']['hostOS'] = $vbox->vbox->host->operatingSystem;
			$response['data']['responseData']['DSEP'] = $vbox->getDsep();
			$response['data']['responseData']['groupDefinitionKey'] = ($settings->phpVboxGroups ? vboxconnector::phpVboxGroupKey : 'GUI/GroupDefinitions');

			$response['data']['success'] = true;

			break;

        case 'resetPassword1':
            global $_SESSION;
            $response['data']['success'] = false;
            $response['data']['error'] = '';
            if (!$request['params']['u']) {
                break;
            }
            $username = $request['params']['u'];
            $_SESSION['valid'] = true;
            $settings = new phpVBoxConfigClass();
            if (!$settings->enablePasswordReset) {
                throw new Exception("Reset password function is disabled.");
            }
            $pathToDatabase = $settings->passwordResetSessionsFile ?? null;

            if ($pathToDatabase === null) {
                throw new Exception('Path to database is not specified');
            }

            $allUsers = $settings->auth->listUsers();

            require_once '../classes/NotificationHelper.php';
            if ($username != 'admin' && isset($allUsers[$username])) {
                $create = !file_exists($pathToDatabase);
                $db = new SQLite3($pathToDatabase, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
                if ($create) {
                    $db->exec("CREATE TABLE sessions (
    username VARCHAR(255) NOT NULL UNIQUE,
    expires INTEGER NULL,
    lastsent INTEGER NOT NULL,
    code VARCHAR(255) NOT NULL
);");
                }

                $db->exec("DELETE FROM sessions WHERE expires < " . time() . ";");
                $dbUsername = SQLite3::escapeString($username);
                $check = $db->query("SELECT exists(SELECT username FROM sessions WHERE username='$dbUsername') AS rowExists;")->fetchArray();
                if ($check['rowExists']) {
                    $response['data']['error'] = "You have recently tried to reset this user's password. Please try again later.";
                    break;
                }
                $code = md5(rand(100000000, 999999999) . " " . rand(100000000, 999999999) . " " . rand(100000000, 999999999) . " " . microtime(true));
                $time = time();
                $expires = $time + 3600 * 2;
                $db->exec("INSERT INTO sessions (username, expires, lastsent, code) VALUES ('$dbUsername', $expires, $time, '$code');");
                NotificationHelper::onPasswordSendConfirmationCode($username, $code, $expires);
                $response['data']['success'] = true;
            } else {
                $response['data']['error'] = "Account does not exist.";
            }
            break;

        case 'resetPasswordResendCode':
            global $_SESSION;
            $response['data']['success'] = false;
            $response['data']['error'] = '';
            if (!$request['params']['u']) {
                break;
            }
            $username = $request['params']['u'];
            $_SESSION['valid'] = true;
            $settings = new phpVBoxConfigClass();
            if (!$settings->enablePasswordReset) {
                throw new Exception("Reset password function is disabled.");
            }
            $pathToDatabase = $settings->passwordResetSessionsFile ?? null;

            if ($pathToDatabase === null) {
                throw new Exception('Path to database is not specified');
            }

            $allUsers = $settings->auth->listUsers();

            require_once '../classes/NotificationHelper.php';

            if ($username != 'admin' && isset($allUsers[$username])) {
                $create = !file_exists($pathToDatabase);
                $db = new SQLite3($pathToDatabase, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
                if ($create) {
                    $db->exec("CREATE TABLE sessions (
    username VARCHAR(255) NOT NULL UNIQUE,
    expires INTEGER NULL,
    lastsent INTEGER NOT NULL,
    code VARCHAR(255) NOT NULL
);");
                }

                $db->exec("DELETE FROM sessions WHERE expires < " . time() . ";");
                $dbUsername = SQLite3::escapeString($username);
                $session = $db->query("SELECT * FROM sessions WHERE username='$dbUsername';")->fetchArray();
                if ($session == null) {
                    $response['data']['error'] = "Session is expired.";
                    break;
                }
                $lastsent = $session['lastsent'];
                $time = time();
                if ($lastsent < $time - 120) {
                    $code = $session['code'];
                    $expires = (int)$session['expires'];
                    $db->exec("UPDATE sessions SET lastsent=$time WHERE username='$dbUsername';");
                    NotificationHelper::onPasswordSendConfirmationCode($username, $code, $expires);
                    $response['data']['success'] = true;
                } else {
                    $response['data']['error'] = "A confirmation code was sent recently. Try again later.";
                }
            } else {
                $response['data']['error'] = "Account does not exist.";
            }
            break;

        case 'resetPassword2':
            global $_SESSION;
            $response['data']['success'] = false;
            $response['data']['error'] = '';
            if (!$request['params']['u'] || !$request['params']['c']) {
                break;
            }
            $username = $request['params']['u'];
            $code = $request['params']['c'];
            $password = $request['params']['p'];
            $checkCodeOnly = (bool)$request['params']['checkCodeOnly'];
            if (!$checkCodeOnly && strlen($password) < 5) {
                $response['data']['error'] = "Password is too weak.";
                break;
            }
            $_SESSION['valid'] = true;
            $settings = new phpVBoxConfigClass();
            if (!$settings->enablePasswordReset) {
                throw new Exception("Reset password function is disabled.");
            }
            $pathToDatabase = $settings->passwordResetSessionsFile ?? null;

            if ($pathToDatabase === null) {
                throw new Exception('Path to database is not specified');
            }

            $allUsers = $settings->auth->listUsers();

            if ($username != 'admin' && isset($allUsers[$username])) {
                $create = !file_exists($pathToDatabase);
                $db = new SQLite3($pathToDatabase, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
                if ($create) {
                    $db->exec("CREATE TABLE sessions (
    username VARCHAR(255) NOT NULL UNIQUE,
    expires INTEGER NULL,
    lastsent INTEGER NOT NULL,
    code VARCHAR(255) NOT NULL
);");
                }

                $db->exec("DELETE FROM sessions WHERE expires < " . time() . ";");
                $dbUsername = SQLite3::escapeString($username);
                $dbCode = SQLite3::escapeString($code);
                $check = $db->query("SELECT exists(SELECT username FROM sessions WHERE username='$dbUsername' AND code='$dbCode') AS rowExists;")->fetchArray();
                if (!$check['rowExists']) {
                    $response['data']['error'] = "Confirmation code is invalid.";
                    break;
                }

                if ($checkCodeOnly) {
                    $response['data']['success'] = true;
                    break;
                }
                $skipExistCheck = true;
                $_SESSION['admin'] = true;
                $settings->auth->updateUser([
                    'u' => $username,
                    'p' => $password,
                    'a' => false
                ], @$skipExistCheck);
                $_SESSION['admin'] = false;
                $response['data']['success'] = true;
                $db->exec("DELETE FROM sessions WHERE username='$dbUsername';");
            } else {
                $response['data']['error'] = "Account does not exist.";
            }
            break;

		/*
		 *
		 * USER FUNCTIONS FOLLOW
		 *
		 */

		/*
		 * Pass login to authentication module.
		 */
		case 'login':


			// NOTE: Do not break. Fall through to 'getSession
			if(!$request['params']['u'] || !$request['params']['p']) {
				break;
			}

			// Session
			session_init(true);

			$settings = new phpVBoxConfigClass();

			if (strtolower($request['params']['u']) == 'admin' && isset($settings->admin_allowed_ips) && count($settings->admin_allowed_ips) > 0) {
				$source_ip = $_SERVER['REMOTE_ADDR'];

				if ($settings->check_cloudflare_ips && IpHelper::isCloudFlareAddress($source_ip)) {
					$source_ip = $_SERVER["HTTP_CF_CONNECTING_IP"] ?? $_SERVER["REMOTE_ADDR"];
				}
				$match_at_least_one = false;
				foreach ($settings->admin_allowed_ips as $admin_ip) {
					if (IpHelper::checkIpMatch($source_ip, $admin_ip)) {
						$match_at_least_one = true;
					}
				}

				if (!$match_at_least_one) {
					throw new Exception("Logging in into 'admin' is not allowed from this IP address!");
				}
			}

			// Try / catch here to hide login credentials
			try {
				$settings->auth->login($request['params']['u'], $request['params']['p']);
			} catch(Exception $e) {
				throw new Exception($e->getMessage(), $e->getCode());
			}

			// We're done writing to session
			if(function_exists('session_write_close'))
				@session_write_close();



		/*
		 * Return $_SESSION data
		 */
		case 'getSession':

			$settings = new phpVBoxConfigClass();
			if(method_exists($settings->auth,'autoLoginHook'))
			{
				// Session
				session_init(true);

				$settings->auth->autoLoginHook();

				// We're done writing to session
				if(function_exists('session_write_close'))
					@session_write_close();

			} else {

				session_init();

			}


			$response['data']['responseData'] = $_SESSION;
			$response['data']['success'] = true;
			break;

		/*
		 * Change phpVirtualBox password. Passed to auth module's
		 * changePassword method.
		 */
		case 'changePassword':

			// Session
			session_init(true);

			$settings = new phpVBoxConfigClass();
			$response['data']['success'] = $settings->auth->changePassword($request['params']['old'],
					                                                       $request['params']['new']);

			// We're done writing to session
			if(function_exists('session_write_close'))
				@session_write_close();

			break;

		/*
		 * Get a list of phpVirtualBox users. Passed to auth module's
		 * getUsers method.
		 */
		case 'getUsers':

			// Session
			session_init();

			// Must be an admin
			if(!$_SESSION['admin']) break;

			$settings = new phpVBoxConfigClass();
			$response['data']['responseData'] = $settings->auth->listUsers();
			$response['date']['success'] = true;

			break;

		/*
		 * Remove a phpVirtualBox user. Passed to auth module's
		 * deleteUser method.
		 */
		case 'delUser':

			// Session
			session_init();

			// Must be an admin
			if(!$_SESSION['admin']) break;

			$settings = new phpVBoxConfigClass();
			$settings->auth->deleteUser($request['params']['u']);

			$response['data']['success'] = true;
			break;

		/*
		 * Edit a phpVirtualBox user. Passed to auth module's
		 * updateUser method.
		 */
		case 'editUser':

			$skipExistCheck = true;
			// Fall to addUser

		/*
		 * Add a user to phpVirtualBox. Passed to auth module's
		 * updateUser method.
		 */
		case 'addUser':

			// Session
			session_init();

			// Must be an admin
			if(!$_SESSION['admin']) break;

			$settings = new phpVBoxConfigClass();
			$settings->auth->updateUser($request['params'], @$skipExistCheck);

			$response['data']['success'] = true;
			break;

		/*
		 * Log out of phpVirtualBox. Passed to auth module's
		 * logout method.
		 */
		case 'logout':

			// Session
			session_init(true);

			$vbox = new vboxconnector();
			$vbox->skipSessionCheck = true;

			$settings = new phpVBoxConfigClass();
			$settings->auth->logout($response);

			session_destroy();

			$response['data']['success'] = true;

			break;


		/*
		 * If the above cases did not match, assume it is a request
		 * that should be passed to vboxconnector.
		 */
		default:

			$vbox = new vboxconnector();


			/*
			 * Every 1 minute we'll check that the account has not
			 * been deleted since login, and update admin credentials.
			 */
			if($_SESSION['user'] && ((intval($_SESSION['authCheckHeartbeat'])+60) < time())) {

				// init session and keep it open
				session_init(true);
				$vbox->settings->auth->heartbeat($vbox);

				// We're done writing to session
				if(function_exists('session_write_close'))
					@session_write_close();

			} else {

				// init session but close it
				session_init();

			}

			/*
			 *  Persistent request data
			 */
			if(is_array($request['persist'])) {
				$vbox->persistentRequest = $request['persist'];
			}


			/*
			 * Call to vboxconnector
			 */
			$vbox->{$request['fn']}($request['params'],array(&$response));


			/*
			 * Send back persistent request in response
			*/
			if(is_array($vbox->persistentRequest) && count($vbox->persistentRequest)) {
				$response['data']['persist'] = $vbox->persistentRequest;
			}
			break;

	} // </switch()>

/*
 * Catch all exceptions and populate errors in the
 * JSON response data.
 */
} catch (Exception $e) {

	// Just append to $vbox->errors and let it get
	// taken care of below
	if(!$vbox || !$vbox->errors) {
		$vbox->errors = array();
	}
	$vbox->errors[] = $e;
}


// Add any messages
if($vbox && count($vbox->messages)) {
	foreach($vbox->messages as $m)
		$response['messages'][] = 'vboxconnector('.$request['fn'] .'): ' . $m;
}
// Add other error info
if($vbox && $vbox->errors) {

	foreach($vbox->errors as $e) { /* @var $e Exception */

		ob_start();
		print_r($e);
		$d = ob_get_contents();
		ob_end_clean();

		# Add connection details to connection errors
		if($e->getCode() == vboxconnector::PHPVB_ERRNO_CONNECT && isset($vbox->settings))
			$d .= "\n\nLocation:" . $vbox->settings->location;

		$response['messages'][] = htmlentities($e->getMessage()).' ' . htmlentities($details);

		$response['errors'][] = array(
			'error'=> ($e->getCode() & vboxconnector::PHPVB_ERRNO_HTML ? $e->getMessage() : htmlentities($e->getMessage())),
			'details'=>htmlentities($d),
			'errno'=>$e->getCode(),
			// Fatal errors halt all processing
			'fatal'=>($e->getCode() & vboxconnector::PHPVB_ERRNO_FATAL),
			// Connection errors display alternate servers options
			'connection'=>($e->getCode() & vboxconnector::PHPVB_ERRNO_CONNECT)
		);
	}
}

/*
 * Return response as JSON encoded data or use PHP's
 * print_r to dump data to browser.
 */
if(isset($request['printr'])) {
	print_r($response);
} else {
    header('Content-type: application/json');
	echo(json_encode($response));
}


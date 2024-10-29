<?php
/**
 * VirtualBox Console Command Sender
 * @author Semyon Bayandin
 * @package phpVirtualBox
 */

require_once(dirname(__FILE__).'/../config.php');

$config = new phpVBoxConfig();

if (!isset($config->vboxmanage)) {
    echo "`vboxmanage` parameter not found in config.php";
    exit;
}

if (!isset($config->use_sudo)) {
    echo "`use_sudo` parameter not found in config.php";
    exit;
}

if (!isset($config->use_vboxinputwebserver)) {
    echo "`use_vboxinputwebserver` parameter not found in config.php";
    exit;
}

# Turn off PHP notices
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_WARNING);

require_once(dirname(__FILE__).'/lib/config.php');
require_once(dirname(__FILE__).'/lib/utils.php');
require_once(dirname(__FILE__).'/lib/vboxconnector.php');

// Check for valid session
global $_SESSION;
session_init();
if(!@$_SESSION['valid']) {
    return;
}

// Clean request
$request = $_POST;

$settings = new phpVBoxConfigClass();
$vbox = new vboxconnector();
$vbox->connect();

$combinations = [
    "tab" => "0f 8f",
    "enter" => "1c 9c",
    "backspace" => "0e 8e",
    "uparrow" => "e0 48 e0 c8",
    "downarrow" => "e0 50 e0 d0",
    "leftarrow" => "e0 4b e0 cb",
    "rightarrow" => "e0 4d e0 cd",
    "insert" => "e0 52 e0 d2",
    "home" => "47 c7",
    "pageup" => "e0 49 e0 c9",
    "pagedown" => "51 d1",
    "end" => "4f cf",
    "delete" => "53 d3",
    "esc" => "01 81",

    "ctrl_alt_del" => "1d 38 53 d3 b8 9d",
    "ctrl_c" => "1d 2e ae 9d",
    "ctrl_v" => "1d 2f af 9d",
    "ctrl_x" => "1d 2d ad 9d",
    "ctrl_z" => "1d 2c ac 9d",
    "ctrl_a_d" => "1d 1e 20 a0 9e 9d",
    "ctrl_l" => "1d 26 a6 9d",
    "ctrl_s" => "1d 1f 9f 9d",

    "shift_alt" => "2a 38 b8 aa",

    "win" => "e0 5b e0 db",
    "win_r" => "e0 5b 13 93 e0 db",
    "win_l" => "e0 5b 26 a6 e0 db",

    "f1" => "3b bb",
    "f2" => "3c bc",
    "f3" => "3d bd",
    "f4" => "3e be",
    "f5" => "3f bf",
    "f6" => "40 c0",
    "f7" => "41 c1",
    "f8" => "42 c2",
    "f9" => "43 c3",
    "f10" => "44 c4",
    "f11" => "57 d7",
    "f12" => "58 d8",
    "alt_f4" => "38 3e be b8"
];

if (!isset($request['vm'])) {
    die("Virtual Machine is not specified");
}

if (!isset($request['input_queue'])) {
    die("Virtual Machine is not specified");
}

try {
    $machine = $vbox->vbox->findMachine($request['vm']);
} catch (Throwable $e) {
    die("Failed to get virtual machine");
}

$groups = $machine->getGroups();

$newGroups = $groups;
if ($settings->replaceSpacesToMail) {
    $newGroups = [];
    foreach ($groups as $group) {
        $newGroups[] = str_replace(" ", "@", $group);
    }
}

if (!$_SESSION['admin'] && !in_array('/'.$_SESSION['user'], $newGroups)) {
    die("You don't have access to this machine");
}

if (!in_array($machine->state->__toString(), ['Running'])) {
    exit;
}

$input_queue = @json_decode(urldecode(base64_decode($request['input_queue'])), true);

if (!$input_queue) {
    ob_start();
    var_dump($input_queue);
    $vd = ob_get_clean();
    die("Invalid input queue. " . base64_decode($request['input_queue']));
}

$new_input_queue = [];

foreach ($input_queue as $item) {
    if (!is_array($item)) {
        continue;
    }
    if (!isset($item['t']) || !in_array($item['t'], ['k', 'c'])) {
        continue;
    }

    if (
        ($item['t'] == 'k' && !isset($item['k'])) ||
        ($item['t'] == 'c' && !isset($item['c']))
    ) {
        continue;
    }

    if ($item['t'] == 'k') {
        $startwith = substr($item['k'], 0, 1);
        $endwith = substr($item['k'], -1);

        $new_input_queue[] = [
            't' => 'k',
            'k' => escapeshellarg($item['k'])
        ];
    } else if ($item['t'] == 'c') {
        if (!isset($combinations[$item['c']])) {
            continue;
        }
        $new_input_queue[] = [
            't' => 'c',
            'c' => $combinations[$item['c']]
        ];
    }
}

if ($config->use_vboxinputwebserver) {
    $postdata = http_build_query(
        array(
            'vboxmanage' => $config->vboxmanage,
            'vm' => $request['vm'],
            'input_queue' => base64_encode(json_encode($new_input_queue))
        )
    );

    $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $postdata
        )
    );

    $context = stream_context_create($opts);

    $result = file_get_contents('http://127.0.0.1:18084', false, $context);
} else {
    foreach ($new_input_queue as $item) {
        if ($item['t'] == 'k') {
            $final_command = ($config->use_sudo ? "sudo " : "") .
                $config->vboxmanage . " controlvm " . $request["vm"] . " keyboardputstring " . $item['k'];

            exec($final_command, $output);
        } else if ($item['t'] == 'c') {
            $final_command = ($config->use_sudo ? "sudo " : "") .
                $config->vboxmanage . " controlvm " . $request["vm"] . " keyboardputscancode " . $item['c'];

            exec($final_command, $output);
        }
    }
}
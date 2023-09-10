<?php
declare(ticks = 1);

/**
 * This application runs on the xRefCore framework and
 * is designed to work entering data into the console of a
 * virtual machine, if direct input to the console for some reason does not work
 *
 * @package VBoxInputServer
 * @author Semyon Bayandin
 */

namespace Program;

use HttpServer\Request;
use HttpServer\Response;
use HttpServer\Server;
use HttpServer\ServerEvents;
use IO\Console;
use Application\Application;

class Main
{
    public function __construct(array $args)
    {
        $server = new Server("127.0.0.1", 18084);

        $server->On(ServerEvents::Request, function(Request $request, Response $response, Server $server) : void
        {
            if (!isset($request->Post["vboxmanage"]))
            {
                $response->End("vboxmanage is not set");
                return;
            }
            $vboxmanage = $request->Post["vboxmanage"];

            if (!isset($request->Post["vm"]))
            {
                $response->End("vm is not set");
                return;
            }
            $vm = $request->Post["vm"];

            if (!isset($request->Post["input_queue"]))
            {
                $response->End("input_queue is not set");
                return;
            }

            $input_queue = json_decode(base64_decode($request->Post["input_queue"]), true);

            foreach ($input_queue as $item) {
                if ($item['t'] == 'k') {
                    $command = $item['k'];
                    $cmd = $vboxmanage . " controlvm " . $vm . " keyboardputstring " . $command;
                    exec($cmd);
                } else if ($item['t'] == 'c') {
                    $combination = $item['c'];
                    $cmd = $vboxmanage . " controlvm " . $vm . " keyboardputscancode " . $combination;
                    exec($cmd);
                }
            }

            $response->End();
        });

        $server->On(ServerEvents::Start, function(Server $server) : void
        {
            Console::WriteLine("VBoxInputServer is running!");
        });

        $server->Start();
    }
}
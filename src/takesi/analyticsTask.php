<?php

namespace takesi;

use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use takesi\analytics;

class AnalyticsTask extends PluginTask
{

    public $plugin;

    public $ran = true;

    public function __construct(analytics $plugin)
    {
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }

    public function getPlugin()
    {
        return $this->plugin;
    }

    public function onRun($tick)
    {
        //1530802800 2018/07/06 00:00:00
        //1530889200 2018/07/07 00:00:00
        //So 1 day =86400s
        if (time() % 86400 == 0) {
            $players = Server::getInstance()->getOnlinePlayers();
            $this->plugin->getLogger()->info("changing day is now going...");
            foreach ($players as $player) {
                $jointime = $this->plugin->pair[$player->getName()];
                $difftime = time() - $jointime;
                $this->plugin->getLogger()->info($player->getName() . " stayed in this server for " . $difftime . "s");
                if ($this->plugin->isExists($player->getName(), date("Ymd", time() - 5000))) {
                    $data = $this->plugin->getPlayerdata($player->getName(), date("Ymd", time() - 5000));
                    var_dump($data);
                    $this->plugin->updatePlayerdata($player->getName(), $data["date"], (((int) $data["time"]) + ((int) $difftime)));
                    $this->plugin->getLogger()->info("So he or she stayed all in this server for " . (((int) $data["time"]) + ((int) $difftime)) . "s today.");
                } else {
                    $this->plugin->createPlayerdata($player->getName(), date("Ymd", time() - 5000), $difftime);
                }
                unset($this->pair[$player->getName()]);
                $this->plugin->pair[$player->getName()] = time();
            }
        }

        //$this->getPlugin()->removeTask($this->getTaskId());
    }
}

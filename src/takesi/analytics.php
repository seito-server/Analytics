<?php

namespace takesi;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\plugin\PluginBase;
use takesi\analyticsTask;

class analytics extends PluginBase implements Listener
{

    public $db;
    public $pair = array();

    public function onEnable()
    {
        $this->getLogger()->notice("Loaded!");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        if (!file_exists($this->getDataFolder())) {
            mkdir($this->getDataFolder(), 0744, true);
            mkdir($this->getDataFolder() . "/" . "skins" . "/", 0744, true);
        }
        $this->db = new \SQLite3($this->getDataFolder() . "/logindata.sqlite3");
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS playerdata(
			name TEXT NOT NULL,
			date TEXT NOT NULL,
			time INTEGER NOT NULL
			)"
        );
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new analyticsTask($this), 20);
    }

    public function isExists($name, $date)
    {
        $que = $this->db->prepare("SELECT * FROM playerdata WHERE name = :name AND date= :now");
        $que->bindValue(":name", $name, SQLITE3_TEXT);
        $que->bindValue(":now", $date, SQLITE3_TEXT);
        $data = $que->execute();
        $result = [];
        while ($d = $data->fetchArray()) {
            $result[] = $d;
        }
        return count($result) > 0;
    }

    public function createPlayerdata($name, $date, $long)
    {
        $this->db->exec("INSERT INTO playerdata VALUES(\"$name\", $date, $long)");
    }

    public function updatePlayerdata($name, $date, $long)
    {
        $this->db->exec("UPDATE playerdata SET time = " . $long . " WHERE name = \"$name\" AND date= " . $date . "");
    }

    public function getPlayerdata($name, $date)
    {
        $que = $this->db->prepare("SELECT * FROM playerdata WHERE name = :name AND date= :now");
        $que->bindValue(":name", $name, SQLITE3_TEXT);
        $que->bindValue(":now", $date, SQLITE3_TEXT);
        $data = $que->execute();
        $result;
        while ($d = $data->fetchArray()) {
            $result = $d;
        }
        return $result;
    }

    public function onJoin(PlayerJoinEvent $event)
    {
        $player = $event->getPlayer();
        $this->pair[$player->getName()] = time();
        if (extension_loaded("gd")) {
            $img = imagecreatetruecolor(64, 32);
            $colors = [];
            $bytes = $player->getSkin()->getSkinData();
            $x = $y = $c = 0;
            while ($y < 32) {
                $cid = substr($bytes, $c, 3);
                if (!isset($colors[$cid])) {
                    $colors[$cid] = imagecolorallocate($img, ord($cid{0}), ord($cid{1}), ord($cid{2}));
                }
                imagesetpixel($img, $x, $y, $colors[$cid]);
                $x++;
                $c += 4;
                if ($x === 64) {
                    $x = 0;
                    $y++;
                }
            }
            imagepng($img, $this->getDataFolder() . "/skins/" . $player->getName() . ".png");
            imagedestroy($img);
        } else {
            $player->sendMessage("Extension is not yet loaded!");
        }

    }

    public function onQuit(PlayerQuitEvent $event)
    {
        var_dump($this->pair);
        $jointime = $this->pair[$event->getPlayer()->getName()];
        $difftime = time() - $jointime;
        $this->getLogger()->info($event->getPlayer()->getName() . " stayed in this server for " . $difftime . "s");
        if ($this->isExists($event->getPlayer()->getName(), date("Ymd"))) {
            $data = $this->getPlayerdata($event->getPlayer()->getName(), date("Ymd"));
            var_dump($data);
            $this->updatePlayerdata($event->getPlayer()->getName(), $data["date"], (((int) $data["time"]) + ((int) $difftime)), date("Ymd"));
            $this->getLogger()->info("So he or she stayed all in this server for " . (((int) $data["time"]) + ((int) $difftime)) . "s today.");
        } else {
            $this->createPlayerdata($event->getPlayer()->getName(), date("Ymd"), $difftime);
        }
        unset($this->pair[$event->getPlayer()->getName()]);
    }

}

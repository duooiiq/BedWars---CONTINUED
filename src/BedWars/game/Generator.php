<?php


namespace BedWars\game;

use BedWars\game\entity\FakeItemEntity;
use BedWars\utils\Utils;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\entity\Location;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\world\Position;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ByteArrayTag;

class Generator
{

    /** @var int $repeatRate */
    private $repeatRate;

    /** @var int $itemID */
    public $itemID;

    /** @var Position $position */
    private $position;

    /** @var bool $spawnText */
    private $spawnText;

    /** @var bool $spawnBlock */
    private $spawnBlock;

    /** @var int $dynamicSpawnTime */
    private $dynamicSpawnTime;

    /** @var FloatingTextParticle $floatingText */
    private $floatingText;

    /** @var $blockEntity */
    private $blockEntity;

    /** @var int $tier */
    private $tier = 1;

    private $multiply = 1;
    /** @var int $multiplied */
    private $multiplied = 0;

    /** @var Team $team */
    public $team;

    const TITLE = [
        ItemIds::DIAMOND => TextFormat::BOLD . TextFormat::AQUA . "Diamond",
        ItemIds::EMERALD => TextFormat::BOLD . TextFormat::GREEN . "Emerald"
    ];

    const FAKE_BLOCK = [
        ItemIds::DIAMOND => ItemIds::DIAMOND_BLOCK,
        ItemIds::EMERALD => ItemIds::EMERALD_BLOCK
    ];


    /**
     * Generator constructor.
     * @param int $itemID
     * @param int $repeatRate
     * @param Position $position
     * @param bool $spawnText
     * @param bool $spawnBlock
     * @param Team|null $team
     */
    public function __construct(int $itemID, int $repeatRate, Position $position, bool $spawnText, bool $spawnBlock, ?Team $team = null)
    {
        $this->itemID = $itemID;
        $this->repeatRate = $repeatRate;
        $this->position = $position;
        $this->spawnText = $spawnText;
        $this->spawnBlock = $spawnBlock;
        $this->team = $team;

        $this->dynamicSpawnTime = $repeatRate;

        if($this->spawnText){
            $text = TextFormat::YELLOW . "Tier " . TextFormat::RED . Utils::rome($this->tier) . "\n".
                self::TITLE[$itemID] . "\n\n".
                TextFormat::YELLOW . "Spawns in " . TextFormat::RED . $this->dynamicSpawnTime . "seconds";
            $this->floatingText = new FloatingTextParticle($text, "");
        }

        if($this->spawnBlock){
           $path = Server::getInstance()->getDataPath() . "plugin_data/BedWars/skins/" . $itemID . ".png";
           $skin = Utils::getSkinFromFile($path, 'geometry.player_head', FakeItemEntity::GEOMETRY);
           $position->add(0.5, 2.3, 0.5);
           $fakeItem = new FakeItemEntity(new Location($position->getX() + 0.5, $position->getY() + 2.3, $position->getZ() + 0.5, $position->getWorld(), 0, 0), $skin);
           $fakeItem->setScale(1.4);
           $fakeItem->spawnToAll();
        }
    }

    public function getPosition() {
        return $this->position;
    }


    /**
     * @param int $repeatRate
     */
    public function setRepeatRate(int $repeatRate) : void{
        $this->repeatRate = $repeatRate;
    }

    public function getRepeatRate() : int{
        return $this->repeatRate;
    }

    public function setMultiply(int $multiply) : void{
        $this->multiply = $multiply;
    }

    public function tick() : void{
        if($this->team instanceof Team){
            if(count($this->team->getPlayers()) <= $this->team->dead){
                /*var_dump($this->team->dead);
                var_dump(count($this->team->getPlayers()))  */
                return;
            }
        }

        if($this->spawnText){
            $text = TextFormat::YELLOW . "Tier " . TextFormat::RED . Utils::rome($this->tier) . "\n".
                self::TITLE[$this->itemID] . "\n".
                TextFormat::YELLOW . "Spawn in " . TextFormat::RED . $this->dynamicSpawnTime;
            $this->floatingText->setText($text);
            foreach($this->floatingText->encode($this->position->asVector3()->add(0.5, 3.3, 0.5)) as $packet){
                foreach($this->position->getWorld()->getPlayers() as $player){
                    $player->getNetworkSession()->sendDataPacket($packet);
                }
            }
        }
        $this->dynamicSpawnTime--;
     
        if($this->dynamicSpawnTime == 0){
            $this->dynamicSpawnTime = $this->repeatRate;
            if($this->itemID == ItemIds::EMERALD && $this->multiply == 200 && $this->team instanceof Team){
                $this->setRepeatRate($this->getRepeatRate() / 4);
                return;
            }
            if($this->multiply == 50){
                if($this->multiplied == 2){
                    $this->multiplied = 0;
                     $this->position->getWorld()->dropItem($this->position->asVector3(), ItemFactory::getInstance()->get($this->itemID, 0, 2));
                }else{
                    $this->position->getWorld()->dropItem($this->position->asVector3(), ItemFactory::getInstance()->get($this->itemID));
                }
                $this->multiplied++;

            }else if($this->multiply == 100){
                $this->position->getWorld()->dropItem($this->position->asVector3(), ItemFactory::getInstance()->get($this->itemID, 0, 2));
            }else if($this->multiply == 200){
                $this->position->getWorld()->dropItem($this->position->asVector3(), ItemFactory::getInstance()->get($this->itemID, 0, 4));
            }else{
                $this->position->getWorld()->dropItem($this->position->asVector3(), ItemFactory::getInstance()->get($this->itemID));
            }
        }
    }

    public function getTier() : int{
        return $this->tier;
    }

    public function updateTier() : void{
        $this->tier++;
        //-20%
        $this->repeatRate = $this->repeatRate - ($this->repeatRate * 100 / 20);
    }

    /**
     * @return FakeItemEntity
     */
    public function getBlockEntity() : ?FakeItemEntity{
        return $this->blockEntity;
    }

    /**
     * @return FloatingTextParticle|null
     */
    public function getFloatingText() : ?FloatingTextParticle{
        return $this->floatingText;
    }




}
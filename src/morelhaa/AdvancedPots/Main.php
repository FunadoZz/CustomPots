<?php

namespace morelhaa\AdvancedPots;

use pocketmine\entity\projectile\SplashPotion;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\PotionType;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener
{
    private float $healingAmount;
    private float $maxDistance;
    private bool $enableSplashPotions;
    private bool $enableDrinkablePotions;
    private bool $playSounds;

    public function onEnable(): void
    {
        @mkdir($this->getDataFolder());

        $this->saveResource("config.yml");
        $this->loadConfiguration();

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info("AdvancedPots habilitado correctamente!");
        $this->getLogger()->info("Curación: {$this->healingAmount} | Distancia: {$this->maxDistance}");
    }

    private function loadConfiguration(): void
    {
        $config = $this->getConfig();

        $this->healingAmount = (float) $config->get("healing-amount", 5.3);
        $this->maxDistance = (float) $config->get("splash-max-distance", 5.5);
        $this->enableSplashPotions = (bool) $config->get("enable-splash-potions", true);
        $this->enableDrinkablePotions = (bool) $config->get("enable-drinkable-potions", true);
        $this->playSounds = (bool) $config->get("play-sounds", true);
    }

    public function onProjectileHit(ProjectileHitEvent $event): void
    {
        if (!$this->enableSplashPotions) {
            return;
        }

        $projectile = $event->getEntity();

        if (!$projectile instanceof SplashPotion) {
            return;
        }

        $owner = $projectile->getOwningEntity();

        if (!$owner instanceof Player || !$owner->isAlive()) {
            return;
        }

        $potionType = $projectile->getPotionType();
        if ($potionType !== PotionType::STRONG_HEALING) {
            return;
        }

        $distance = $projectile->getPosition()->distance($owner->getPosition());

        if ($distance > $this->maxDistance) {
            return;
        }

        $this->healPlayer($owner);
    }

    public function onItemUse(PlayerItemUseEvent $event): void
    {
        if (!$this->enableDrinkablePotions) {
            return;
        }

        $player = $event->getPlayer();
        $item = $event->getItem();

        if ($item->getTypeId() !== VanillaItems::POTION()->getTypeId()) {
            return;
        }

        if (!$item instanceof \pocketmine\item\Potion) {
            return;
        }

        $potionType = $item->getType();

        if ($potionType === PotionType::STRONG_HEALING) {
            $this->healPlayer($player);

            $item->setCount($item->getCount() - 1);
            $player->getInventory()->setItemInHand($item);

            $event->cancel();
        }
    }

    private function healPlayer(Player $player): void
    {
        $currentHealth = $player->getHealth();
        $maxHealth = $player->getMaxHealth();
        $newHealth = min($currentHealth + $this->healingAmount, $maxHealth);

        $player->setHealth($newHealth);

        if ($this->playSounds) {
            $player->getWorld()->addSound($player->getPosition(), new \pocketmine\world\sound\BlazeShootSound());
        }
    }

    public function onDisable(): void
    {
        $this->getLogger()->info("AdvancedPots deshabilitado correctamente!");
    }
}

//Onichiwa

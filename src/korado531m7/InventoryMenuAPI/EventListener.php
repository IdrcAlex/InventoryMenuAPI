<?php
namespace korado531m7\InventoryMenuAPI;

use korado531m7\InventoryMenuAPI\InventoryMenuAPI as IM;
use korado531m7\InventoryMenuAPI\event\InventoryClickEvent;
use korado531m7\InventoryMenuAPI\event\InventoryCloseEvent;

use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;

class EventListener implements Listener{
    public function __construct(){
    }
    
    public function onQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        if(InventoryMenuAPI::isOpeningInventoryMenu($player)){
            InventoryMenuAPI::unsetData($player);
        }
    }
    
    public function onReceive(DataPacketReceiveEvent $event){
        $pk = $event->getPacket();
        $player = $event->getPlayer();
        if($pk instanceof ContainerClosePacket && InventoryMenuAPI::isOpeningInventoryMenu($player)){
            $instance = $data[IM::TEMP_IM_INSTANCE];
            $data = InventoryMenuAPI::getData($player);
            $ev = new InventoryCloseEvent($player, $data[IM::TEMP_FMINV_INSTANCE], $pk->windowId);
            $ev->call();
            if($ev->isCancelled()){
                $instance->removeBlock($player);
                $instance->send($player);
            }else{
                $instance->close($player);
            }
        }elseif($pk instanceof InventoryTransactionPacket){
            if(InventoryMenuAPI::isOpeningInventoryMenu($player) && array_key_exists(0,$pk->actions)){
                $data = InventoryMenuAPI::getData($player);
                $instance = $data[IM::TEMP_INV_INSTANCE];
                $action = $pk->actions[0];
                if($instance->isReadonly()){
                    $instance->close($player);
                    $player->getInventory()->setContents($data[IM::TEMP_INV_CONTENTS]);
                    $event->setCancelled();
                }
                $ev = new InventoryClickEvent($player, $action->oldItem->getId() === ItemIds::AIR ? $action->newItem : $action->oldItem, $pk, $data[IM::TEMP_FMINV_INSTANCE]);
                $ev->call();
                if($instance->getCallable() !== null){
                    call_user_func_array($instance->getCallable(), [$player, $instance]);
                }
            }
        }
    }
}
<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Driver;
use Bitrix\Bizproc\Activity\PropertiesDialog;

// Wrong path 945673

class CBPRockotCrmDiskRename extends CBPActivity
{
    public $dealId;
    public $newName;

    private static $UF_DEAL = "UF_CRM_1679410842";
    private static $UF_DISK = 'UF_CRM_1679410808';
    private static $COMMON_DISK_ID = 19;
    private static $COMMON_STORAGE_ID = 11;
    private static $IS_DEBUG = true;

    public function __construct($name)
    {
        parent::__construct($name);
        $this->arProperties = array("dealId", "newName");
    }

    public function Execute()
    {
        CBPRockotCrmDiskRename::debugInLog("\n\n> Start BP");

        // Check modules
        if (!CBPRockotCrmDiskRename::checkModules()) {
            CBPRockotCrmDiskRename::debugInLog("> Error: no needed modules");
            return CBPActivityExecutionStatus::Closed;
        }

        CBPRockotCrmDiskRename::debugInLog("> All modules is included");
        
        // Get Deal info
        $deal = CBPRockotCrmDiskRename::getDealInfo($this->GetDocumentId());
        if (!$deal) {
            CBPRockotCrmDiskRename::debugInLog("> Error: group found");
            return CBPActivityExecutionStatus::Closed;
        }

        CBPRockotCrmDiskRename::debugInLog("> Has deal info");
        CBPRockotCrmDiskRename::debugInLog("--> dealId: ".$deal["dealId"]);
        CBPRockotCrmDiskRename::debugInLog("--> groupID: ".$deal["groupId"]);
        CBPRockotCrmDiskRename::debugInLog("--> title:".$deal["title"]);
        CBPRockotCrmDiskRename::debugInLog("--> diskUrl:".$deal["diskUrl"]);

        $folders = CBPRockotCrmDiskRename::getFoldersPathByURL($deal["diskUrl"]);
        if (!$folders) {
            CBPRockotCrmDiskRename::debugInLog("> Error: can not get folders path");
            return CBPActivityExecutionStatus::Closed;
        }

        CBPRockotCrmDiskRename::debugInLog("> Has folders path");
        CBPRockotCrmDiskRename::debugInLog("--> mainFolder: ".$folders["mainFolder"]);
        CBPRockotCrmDiskRename::debugInLog("--> subFolder: ".$folders["subFolder"]);

        $mainFolderObject = CBPRockotCrmDiskRename::findFolderByName($folders["mainFolder"], CBPRockotCrmDiskRename::$COMMON_STORAGE_ID, CBPRockotCrmDiskRename::$COMMON_DISK_ID);
        if (!$mainFolderObject) {
            CBPRockotCrmDiskRename::debugInLog("> Error: can not find main folder");
            return CBPActivityExecutionStatus::Closed;
        }

        CBPRockotCrmDiskRename::debugInLog("> Has main folder");
        CBPRockotCrmDiskRename::debugInLog("--> mainFolderObject: ".$mainFolderObject["ID"]);

        $subFolderObject = CBPRockotCrmDiskRename::findFolderByName($folders["subFolder"], CBPRockotCrmDiskRename::$COMMON_STORAGE_ID, $mainFolderObject["ID"]);
        if (!$subFolderObject) {
            CBPRockotCrmDiskRename::debugInLog("> Error: can not find sub folder");
            return CBPActivityExecutionStatus::Closed;
        }

        CBPRockotCrmDiskRename::debugInLog("> Has sub folder");
        CBPRockotCrmDiskRename::debugInLog("--> subFolderObject: ".$subFolderObject["ID"]);

        $success = CBPRockotCrmDiskRename::renameByRootObjectId(CBPRockotCrmDiskRename::$COMMON_STORAGE_ID, $deal["title"], $subFolderObject["ID"]);
        if (!$success) {
            CBPRockotCrmDiskRename::debugInLog("> Error: can not rename folder");
            return CBPActivityExecutionStatus::Closed;
        }

        CBPRockotCrmDiskRename::debugInLog("> Folder has been renamed");

        $newUrl = CBPRockotCrmDiskRename::replaceLastPartInUrl($deal["diskUrl"], urlencode($deal["title"]));
        CBPRockotCrmDiskRename::debugInLog("> New URL: ".$newUrl);

        $success = CBPRockotCrmDiskRename::updateDiskFieldInDeal($deal["dealId"], $newUrl);
        if (!$success) {
            CBPRockotCrmDiskRename::debugInLog("> Error: can not update deal");
            return CBPActivityExecutionStatus::Closed;
        }

        CBPRockotCrmDiskRename::debugInLog("> Deal has been updated");
        
        
        CBPRockotCrmDiskRename::debugInLog("> Done");

        return CBPActivityExecutionStatus::Closed;
    }


    public static function replaceLastPartInUrl($url, $newPart) {
        $parsed = parse_url($url);
        $path = $parsed["path"];
        $parts = explode("/", $path);
        $parts[count($parts) - 1] = $newPart;
        return implode("/", $parts);
    }

    public static function updateDiskFieldInDeal($dealId, $diskLink) {
        $CCrmDeal = new CCrmDeal();
        $fieldsToUpdate = [CBPRockotCrmDiskRename::$UF_DISK => $diskLink];

        if (!$CCrmDeal->Update($dealId, $fieldsToUpdate)) {
            CBPRockotCrmDiskRename::debugInLog('Ошибка обновления: ' . $CCrmDeal->LAST_ERROR);
            return false;
        } 
        return true;
        
    }

    public static function GetPropertiesDialog($documentType, $activityName, $workflowTemplate, $workflowParameters, $workflowVariables, $currentValues = null, $formName = "")
    {
        $runtime = CBPRuntime::GetRuntime();
        return $runtime->ExecuteResourceFile(
            __FILE__,
            "properties_dialog.php",
            array(
                "formName" => $formName,
                "dialog" => new PropertiesDialog(
                    $activityName,
                    $workflowTemplate,
                    $workflowParameters,
                    $workflowVariables,
                    $currentValues
                ),
            )
        );
    }

    

    /**
     * Check modules
     */
    private static function checkModules() {
        if (!Loader::includeModule('disk') || !Loader::includeModule('crm')) {
            return false;
        }
        return true;
    }

    /**
     * Get current deal
     */
    private function getDealInfo($documentId) {
        $result = ["groupId" => null, "title" => null];
        
        if (strpos($documentId[2], 'DEAL_') != 0) {
            return false;
        }

        $dealId = substr($documentId[2], 5);
        $groupInfo = CBPRockotCrmDiskRename::getGroupIdByDeal($dealId); 
        if (!$groupInfo) {
            return false;
        }

        $result["dealId"] = $dealId;
        $result["groupId"] = $groupInfo["groupId"];
        $result["title"] = $groupInfo["title"];
        $result["diskUrl"] = $groupInfo["diskUrl"];

        return $result;
    }

    /**
     * Get group for deal
     */
    public static function getGroupIdByDeal($dealId) {
        $dbRes = CCrmDeal::GetListEx(
            [], 
            ["ID" => $dealId], 
            false, 
            false, 
            ["TITLE", CBPRockotCrmDiskRename::$UF_DEAL, CBPRockotCrmDiskRename::$UF_DISK]
        );
        while ($deal = $dbRes->Fetch()) {
            $currentUrl = $deal[CBPRockotCrmDiskRename::$UF_DEAL];
            $diskUrl = $deal[CBPRockotCrmDiskRename::$UF_DISK];
            if (!$currentUrl) {
                return null;
            }

            $parsed = parse_url($currentUrl);
            $groupId = explode("/", $parsed["path"])[3];
            
            if (!$groupId) {
                return null;
            }
            return [
                "groupId" => $groupId, 
                "title" => $deal["TITLE"], 
                "diskUrl" => $diskUrl
            ];
        }
        return null;
    }

    /**
     * Rename folter
     */
    public static function renameFolder($groupId, $dealTitle) {
        $driver = \Bitrix\Disk\Driver::getInstance(); 
	    $storage = $driver->getStorageByGroupId($groupId);

        if(!$storage->rename($dealTitle)){
            return false;
        }

        $rootObjectId = $storage->getRootObjectId();
        CBPRockotCrmDiskRename::debugInLog($rootObjectId);
        CBPRockotCrmDiskRename::testMe($rootObjectId, $dealTitle);

        return true;
    }

    /**
     * Get folder by URL
     */
    private static function getFoldersPathByURL($url) {
        $result = ["mainFolder" => null, "subFolder" => null];
        $parsed = parse_url($url);
        $path = $parsed["path"];
        $parts = explode("/path/", $path);
        if ($parts[1]) {
            $separeted = explode("/", $parts[1]);
            $result["mainFolder"] = urldecode($separeted[0]);
            $result["subFolder"] = urldecode($separeted[1]);
            return $result;
        }
        return null;
    }

    

    private static function testMe($realObjectId, $newName) {
        $filter = array(
            '=REAL_OBJECT_ID' => $realObjectId,
            'TYPE' => \Bitrix\Disk\Internals\ObjectTable::TYPE_FOLDER // Фильтрация только папок
        );
        
        $foldersList = \Bitrix\Disk\Internals\FolderTable::getList(array(
            'filter' => $filter,
            'select' => array('*') // Выбор всех полей
        ));
        
        while ($folder = $foldersList->fetch()) {
            $_log = "Название папки (" .$folder["ID"] . "): "  . $folder['NAME'] . "; " .$folder["STORAGE_ID"] . "\n";
            CBPRockotCrmDiskRename::debugInLog($_log);
            $_r = CBPRockotCrmDiskRename::renameByRootObjectId($folder["STORAGE_ID"], $newName, $folder["ID"]);
            CBPRockotCrmDiskRename::debugInLog($_r ? "Успешно" : "Ошибка");
        }
    }

    private static function findFolderByName($name, $storageId, $parentId) {
        $filter = array(
          'STORAGE_ID' => $storageId,
          'PARENT_ID' => $parentId,
          'NAME' => $name,
          'TYPE' => \Bitrix\Disk\Internals\ObjectTable::TYPE_FOLDER
        );
      
        $foldersList = \Bitrix\Disk\Internals\FolderTable::getList(array(
            'filter' => $filter,
            'select' => array('*')
        ));
          
        while ($folder = $foldersList->fetch()) {
          return $folder;
        }
        return null;
    }

    private static function renameByRootObjectId($storageId, $newName, $objectId) {

        global $USER;
        $_u = "[".$USER->GetID()."] (".$USER->GetLogin().") ".$USER->GetFullName();
        CBPRockotCrmDiskRename::debugInLog(var_export($_u, true));

        /*
	    $storage = \Bitrix\Disk\Storage::loadById($storageId);

        if(!$storage->rename($newName)){
            
            $errors = $storage->getErrors();
            CBPRockotCrmDiskRename::debugInLog(var_export($errors, true));
            

            $root = $storage->getRootObject();
            if(!$root->rename($newName)) {
                $errors = $storage->getErrors();
                CBPRockotCrmDiskRename::debugInLog(var_export($errors, true));
                return false;
            }
        }
        */

        // $objectId = 123; // ID объекта, который вы хотите обновить
        // $newName = "Новое имя"; // Новое имя объекта

        // $object = \Bitrix\Disk\BaseObject::loadById($objectId);
        // if ($object) {
        //     $object->rename($newName); // Второй параметр - ID пользователя, выполняющего операцию
        //     // echo "Имя объекта обновлено.";
        //     return true;
        // } else {
        //     // echo "Объект не найден.";
        //     return false;
        // }

        // $folder = \Bitrix\Disk\Folder::loadById($objectId);
        $folder = \Bitrix\Disk\Folder::loadById($objectId);
        if (!$folder) {
            CBPRockotCrmDiskRename::debugInLog("!!! EMPTY !!!");
            return false;
        }
        $success = $folder->renameInternal($newName);
        if(!$success){
            $errors = $storage->getErrors();
            CBPRockotCrmDiskRename::debugInLog(var_export($errors, true));
            CBPRockotCrmDiskRename::debugInLog("$objectId; $newName; $storageId");
            return false;
        }

        CBPRockotCrmDiskRename::debugInLog(">>>>>>>>>>>>>>>>>>>!!!!!>>>>>>>>>>>>>>>>>>>>");

        return $success;
    }

    private static function changeUserToAdmin() {
        global $USER;
        $USER->Authorize(84);
    }

    /**
     * Log in file
     */
    private static function debugInLog($message) {
        if (CBPRockotCrmDiskRename::$IS_DEBUG) {
            _printBP_($message);
        }
    }
}

function _printBP_($mes) {
	file_put_contents($_SERVER['DOCUMENT_ROOT']."/deb.log", $mes."\n", FILE_APPEND);
}
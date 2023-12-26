<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Driver;
use Bitrix\Bizproc\Activity\PropertiesDialog;

class CBPRockotCrmDiskRename extends CBPActivity
{
    public $dealId;
    public $newName;

    private static $UF_DEAL = "UF_CRM_1679410842";
    private static $UF_DISK = 'UF_CRM_1679410808';
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
        CBPRockotCrmDiskRename::debugInLog("--> groupID: ".$deal["groupId"]);
        CBPRockotCrmDiskRename::debugInLog("--> title:".$deal["title"]);
        CBPRockotCrmDiskRename::debugInLog("--> title:".$deal["diskUrl"]);

        // CBPRockotCrmDiskRename::debugInLog(1);
        // $_folder_path = CBPRockotCrmDiskRename::getFolderPathByURL($deal["diskUrl"]);
        // CBPRockotCrmDiskRename::debugInLog(2);
        // CBPRockotCrmDiskRename::renameFolderByPath($_folder_path);
        // CBPRockotCrmDiskRename::debugInLog(3);
        // CBPRockotCrmDiskRename::debugInLog("--> title:".$_folder_path);
        // CBPRockotCrmDiskRename::debugInLog(4);

        // if (!$_folder_path) {
        //     return CBPActivityExecutionStatus::Closed;
        // }

        // Get folder by group
        // $folder = CBPRockotCrmDiskRename::getFolderByGroupId($deal["groupId"]);
        // $folderPath = CBPRockotCrmDiskRename::getFolderPathByURL($folder->getExternalLink(array("createByExternalLink" => true)));
        // CBPRockotCrmDiskRename::debugInLog("> Folder path: ".$folderPath);
        
        // Rename folder in group
        $renameStatus = CBPRockotCrmDiskRename::renameFolder($deal["groupId"], $deal["title"]);
        if (!$renameStatus) {
            CBPRockotCrmDiskRename::debugInLog("> Error: can not rename folder");
            return CBPActivityExecutionStatus::Closed;
        }

        CBPRockotCrmDiskRename::debugInLog("> Folder has been renamed");

        // CBPRockotCrmDiskRename::debugInLog(($renameStatus));
        CBPRockotCrmDiskRename::debugInLog("> Done");

        return CBPActivityExecutionStatus::Closed;
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

        // if(!$storage->rename($dealTitle)){
        //     return false;
        // }

        $rootObjectId = $storage->getRootObjectId();
        CBPRockotCrmDiskRename::debugInLog($rootObjectId);
        CBPRockotCrmDiskRename::testMe($rootObjectId, $dealTitle);

        return true;
    }

    /**
     * Get folder by URL
     */
    private static function getFolderPathByURL($url) {
        $parsed = parse_url($url);
        $path = $parsed["path"];
        $parts = explode("/path/", $path);
        if ($parts[1]) {
            return $parts[1];
        }
        return "";
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
            $_r = CBPRockotCrmDiskRename::renameByRootObjectId($folder["STORAGE_ID"], $newName);
            CBPRockotCrmDiskRename::debugInLog($_r ? "Успешно" : "Ошибка");
        }
    }

    private static function renameByRootObjectId($storageId, $newName) {
	    $storage = \Bitrix\Disk\Storage::loadById($storageId);

        if(!$storage->rename($newName)){
            
            $errors = $storage->getErrors();
            
            CBPRockotCrmDiskRename::debugInLog((string) $errors);
            CBPRockotCrmDiskRename::debugInLog(var_export($errors, true));

            $root = $storage->getRootObject();
            
            if(!$root->rename($newName)) {
                $errors = $storage->getErrors();
                CBPRockotCrmDiskRename::debugInLog((string) $errors);
                CBPRockotCrmDiskRename::debugInLog(var_export($errors, true));
                return false;
            }
        }

        return true;
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
<?php
/**
 * WPAutoloader for wordpress projects and more!
 * @author Grano22Dev - Adrian Błasiak
 * @version 1.0
 * 
 * @since 1.0 Added resolver to psrNX
 */
$autoloaderPresets = [];
function wpa_define(string $constantName, $constantValue, bool $asGlobalConstant=true) : bool {
    global $autoloaderPresets;
    if(array_key_exists($constantName, $autoloaderPresets)) return false;
    if($asGlobalConstant && !defined("WPA_LOGSPATH")) define("WPA_$constantName", $constantValue); else return false;
    $autoloaderPresets[$constantName] = $constantValue;
    return true;
}
function wpa_get(string $mixedName) {
    global $autoloaderPresets;
    if(!array_key_exists($mixedName, $autoloaderPresets)) return null;
    return $autoloaderPresets[$mixedName];
}
wpa_define("ROOTPATH", str_replace( "vendor".DIRECTORY_SEPARATOR."autoload.php", "", __FILE__ ));
wpa_define("BASEPATH", wpa_get("ROOTPATH")."includes".DIRECTORY_SEPARATOR);
wpa_define("LOGSPATH", wpa_get("ROOTPATH")."vendor".DIRECTORY_SEPARATOR."logs".DIRECTORY_SEPARATOR);

include "global.autoload.php";

class PsrNXLoaderException extends Exception {

}

class PsrNXLoader {
    /**
     * Local Presets
     * @var array $localPresets
     */
    private $localPresets = [];

    /**
     * Autoloader config
     * @staticvar array CONFIG
     */
    const CONFIG = [
        "allowedPredefinedPrefixes"=>true,
        "prefixesChain"=>0, //0 - off, 1 - in path part only, 2 - in multiple parts, 3 - both
        "letterCaseMode"=>0, //0 - none, 1 - only lowercases, 2 - only uppercases
        "classNamesInEnd"=>false,
        "useMap"=>false,
        "logErrors"=>true,
        "logsLang"=>"en_EN",
        "logsPath"=>WPA_LOGSPATH,
        "baseName"=>"Project"
    ];

    /**
     * Local autoloader config
     * @var array $localConfig
     */
    private $localConfig = [];

    /**
     * Local errors
     * @var array $errors
     */
    private $errors = [];
    
    function __construct(array $config=[]) {
        if(count($config)>0) $this->configure($config);
    }

    /**
     * Getters
     * @param string $name Getter name
     * @return mixed
     */
    function __get(string $name) {

    }

    /**
     * Use hook
     */
    function use($nsPath) {
        $fromPath = self::resolve($nsPath);
        $previousClass = get_declared_classes();
        include $fromPath;
        $nextClass = array_diff($previousClass, get_declared_classes());
        return $nextClass;
    }

    /**
     * Split path part by undercores
     * @static splitPathPartUndercore
     * @param $pathPart
     * @return array
     */
    public static function splitPathPartUndercore(string $pathPart) : array {
        $lastStr = ""; $outPrefixes = [];
        $maxLen = strlen($pathPart);
        try {
            for($chr=0;$chr<$maxLen;$chr++) {
                $chrNum = ord($pathPart[$chr]);
                if($chr==$maxLen-1) {
                    $lastStr .= $pathPart[$chr];
                    array_push($outPrefixes, $lastStr);
                } else if($pathPart[$chr]=="_" && $lastStr!="") {
                    $canBeDivided = true;
                    if($chr + 1<$maxLen && $pathPart[$chr + 1]=="_") $canBeDivided = false;
                    if($chr - 1>=0 && $pathPart[$chr - 1]=="_") $canBeDivided = false;
                    if($canBeDivided) {
                        array_push($outPrefixes, $lastStr);
                        $lastStr = "";
                    } else { if($chr - 1 && $pathPart[$chr - 1]!="_") $lastStr .= $pathPart[$chr]; }
                } else if(($chrNum>=64 && $chrNum<=90) || ($chrNum>61 && $chrNum<122) || ($chrNum>=47 && $chrNum<=57)) {
                    $lastStr .= $pathPart[$chr];
                } else throw new Exception("Unknown character given ({$pathPart[$chr]}) in input string path on position $chr");
            }
            return $outPrefixes;
        } catch(Exception $err) {

        }
    }
    
    /**
     * Join path parts into namespace
     * @param string $pathpart,... Path parts to join
     * @return string
     */
    public static function joinsNS() : string {
        $pathParts = func_get_args();
        foreach($pathParts as $argName=>$argValue) {

        }
    }

    /**
     * Resolve path paths and join into psrNX model
     * @param string $pathpart,... Path parts to join
     * @return string
     */
    public function join() : string {
        $pathParts = func_get_args();
        $expectedPath = "";
        $totalParts = count($pathParts) - 1;
        for($i=0;$i<$totalParts;$i++) {
            if($i===0 && $pathParts[$i]==="/") $expectedPath =  wpa_get("ROOTPATH") . $expectedPath;
            else if($i===0 && $pathParts[$i]===$this->getConfig("baseName")) $expectedPath = wpa_get("BASEPATH") . $expectedPath;
            else if(array_key_exists($pathParts[$i], $autoloaderPresets)) $expectedPath = $autoloaderPresets[$pathParts[$i]] . $expectedPath;
            else if($this->getConfig("allowedPredefinedPrefixes") && !$alreadyPrefixed && strtolower($pathParts[$i])=="classes") { $expectedPath = "class." . $expectedPath; $alreadyPrefixed = true; }
            else if($this->getConfig("allowedPredefinedPrefixes") && !$alreadyPrefixed && strtolower($pathParts[$i])=="exceptions") { $expectedPath = $expectedPath . "exception."; $alreadyPrefixed = true; }
            else if(!$alreadyPrefixed && strlen($pathParts[$i])>1 && strpos($pathParts[$i], "_")===0) { 
                if($pathParts[$i][1]==="_") { 
                    $expectedPath = ltrim($pathParts, $pathParts[0]) . $expectedPath;
                } else {
                    if($this->getConfig("prefixesChain")>1) {
                        $matchesExp = self::splitPathPartUndercore($pathParts[$i]);
                        foreach($matchesExp as $matchInd=>$matchUnd) $expectedPath = ".".$matchUnd . $expectedPath;
                    } else $expectedPath = ".".ltrim($pathParts, $pathParts[0]) . $expectedPath;
                    if($this->getConfig("prefixesChain")===0) $alreadyPrefixed = true;
                }
            }
            else if($i===$totalParts) $expectedPath = $pathParts[$i] . ".php" . $expectedPath;
            else $expectedPath = $pathParts[$i] . DIRECTORY_SEPARATOR . $expectedPath;
        }
    }

    /**
     * Resolve full path into psrNX model
     * @param string $psrNamespace Required namespace or class name
     * @return string
     */
    public function resolve(string $psrNamespace) : string {
        global $autoloaderPresets;
        try {
            if(strpos($psrNamespace, "\\")!==false) {
                $pathParts = explode("\\", $psrNamespace);
                if(!$this->getConfig("classNamesInEnd")) { $classNameFromNS = array_pop($pathParts); }
                $totalParts = count($pathParts) - 1;
                $alreadyPrefixed = false;
                $expectedPath = "";
                for($i=$totalParts;$i>=0;$i--) {
                    if($i===0 && $pathParts[$i]==="") $expectedPath =  wpa_get("ROOTPATH") . $expectedPath;
                    else if($i===0 && $pathParts[$i]===$this->getConfig("baseName")) $expectedPath = wpa_get("BASEPATH") . $expectedPath;
                    else if(array_key_exists($pathParts[$i], $autoloaderPresets)) $expectedPath = $autoloaderPresets[$pathParts[$i]] . $expectedPath;
                    else if($this->getConfig("allowedPredefinedPrefixes") && !$alreadyPrefixed && strtolower($pathParts[$i])=="classes") { $expectedPath = "class." . $expectedPath; $alreadyPrefixed = true; }
                    else if($this->getConfig("allowedPredefinedPrefixes") && !$alreadyPrefixed && strtolower($pathParts[$i])=="exceptions") { $expectedPath = $expectedPath . "exception."; $alreadyPrefixed = true; }
                    else if(!$alreadyPrefixed && strlen($pathParts[$i])>1 && strpos($pathParts[$i], "_")===0) { 
                        if($pathParts[$i][1]==="_") { 
                            $expectedPath = ltrim($pathParts, $pathParts[0]) . $expectedPath;
                        } else {
                            if($this->getConfig("prefixesChain")>1) {
                                $matchesExp = self::splitPathPartUndercore($pathParts[$i]);
                                foreach($matchesExp as $matchInd=>$matchUnd) $expectedPath = $matchUnd . "." . $expectedPath;
                            } else $expectedPath = ltrim($pathParts[$i], $pathParts[$i][0]) . "." . $expectedPath;
                            if($this->getConfig("prefixesChain")===0) $alreadyPrefixed = true;
                        }
                    }
                    else if($i===$totalParts) $expectedPath = $pathParts[$i] . ".php" . $expectedPath;
                    else $expectedPath = $pathParts[$i] . DIRECTORY_SEPARATOR . $expectedPath;
                }
                if(isset($classNameFromNS)) { if(strrpos($expectedPath, ".php")+4===strlen($expectedPath)) $expectedPath .= "::".$classNameFromNS; else $expectedPath .= $classNameFromNS.".php"; }
            } else {
                $expectedPath = wpa_get("BASEPATH").$psrNamespace.".php";
            }
            return $expectedPath;
        } catch(Exception $err) {
            return "NULL";
        }
    }

    /**
     * Autoload classes
     */
    public function autoload() : void {
        $thisRef = $this;
        if(function_exists("spl_autoload_register")) {
            spl_autoload_register(function ($nsPath) use ($thisRef) {
                $className = "";
                $expectedPath = $thisRef->resolve($nsPath);
                if(file_exists($expectedPath)) include $expectedPath; else $thisRef->addError(0, [$expectedPath]);
                if(!class_exists($nsPath)) $thisRef->addError(1, [$nsPath, $expectedPath]);
            });
        } else {
            function __autoload($classPath) {
                $expectedPath = wpa_get("BASEPATH").$classPath.".php";
                if(file_exists($expectedPath)) include $expectedPath; else $thisRef->addError(0, [$expectedPath]);
                if(!class_exists($className)) $thisRef->addError(1, [$classPath, $expectedPath]);
            }
        }
    }

    /**
     * Define globals in object scope
     * @param string $localName
     * @param mixed $localValue
     * @return bool
     */
    public function define(string $localName, $localValue) : bool {
        if(!array_key_exists($localName, $this->localPresets)) { $this->localPresets[$localName] = $localValue; return true; } else return false;
    }

    /**
     * Get global in object scope
     * @param string $localName
     * @return mixed
     */
    public function get(string $localName) {
        return array_key_exists($localName, $this->localPresets) ? $this->localPresets[$localName] : null;
    }

    /**
     * Configure autoloader object
     * @param array $assocConfig
     */
    public function configure(array $assocConfig) {
        foreach($assocConfig as $assocConfigIndex=>$assocConfigVal) {
            if(array_key_exists($assocConfigIndex, self::CONFIG)) $localConfig[$assocConfigIndex] = $assocConfigVal;
        }
    }

    /**
     * Get config
     * @param string $optionName
     * @param array $varConfig
     * @return mixed
     */
    public function getConfig(string $optionName, array $varConfig=[]) {
        if(array_key_exists($optionName, self::CONFIG)) {
            if(array_key_exists($optionName, $varConfig)) return $varConfig;
            return array_key_exists($optionName, $this) ? $this[$optionName] : self::CONFIG[$optionName];
        }
    }

    /**
     * AddError
     * @param int $errNum
     * @param array $errParam
     */
    public function addError(int $errNum, array $errParam) {
        $errMess = self::resolveError($errNum, $errParam);
        echo $errMess."<br>";
        array_push($this->errors, $errMess);
        error_log($errMess, 0, $this->getConfig("logsPath")."errors.log");
    }

    /**
     * @static Resolve Error
     * @param int $errNum
     * @param array $errParam
     * @return array
     */
    public static function resolveError(int $errNum, array $errParam, string $lang="en_EN") {
        $message = ""; $logDateTime = date('Y-m-d H:i:s'); $globalMess = "%s:";
        switch($errNum) {
            case 0:
                switch($lang) {
                    default: case "en_EN": $message = "Requested file doesn't exists on %s"; break;
                    case "pl_PL": $message = "Rządany plik nie istnieje w ścieżce %s"; break;
                }
                return sprintf($globalMess.$message, $logDateTime, $errParam[0]);
            break;
            case 1:
                switch($lang) {
                    default: case "en_EN": return sprintf("Requested class %s doesn't exists on path %s", $errParam[0], $errParam[1]);
                }
            break;
        }
    }
}

$globalLoader = new PsrNXLoader();
$globalLoader->autoload();
?>
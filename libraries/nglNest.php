<?php
/*
# Nogal
*the most simple PHP Framework* by hytcom.net
GitHub @hytcom
___
  
# nest
## nglNest *extends* nglBranch [2018-10-29]
Nest es la herramienta para crear y mantener la estructura de base de datos del objeto [owl](https://github.com/hytcom/wiki/blob/master/nogal/docs/owl.md), en MySQSL.  

https://github.com/hytcom/wiki/blob/master/nogal/docs/nest.md
https://github.com/hytcom/wiki/blob/master/nogal/docs/owluso.md

*/
namespace nogal;

class nglNest extends nglBranch {

	private $owl;
	private $aTypes;
	private $aFields;
	private $aPresets;
	private $aPresetFields;
	private $aStarred;
	private $aLoadData;
	private $aLoadDataIndex;
	private $aNormalize;
	private $bUpdate;
	private $aAdd;
	private $bAlterField;
	private $aAlterTable;
	private $aAlterField;
	private $bRegenerate;

	final protected function __declareArguments__() {
		$vArguments							= array();
		$vArguments["about"]				= array('$mValue', null);
		$vArguments["after"]				= array('$mValue', true);
		$vArguments["core"]					= array('$mValue', false);
		$vArguments["db"]					= array('$mValue', null);
		$vArguments["der"]					= array('$mValue', false);
		$vArguments["structure"]			= array('$mValue', null);
		$vArguments["newname"]				= array('$mValue', null);
		$vArguments["select"]				= array('$this->SetObject($mValue)', null);
		$vArguments["type"]					= array('$mValue', "varchar");
		$vArguments["field"]				= array('$mValue', null);
		$vArguments["using"]				= array('$mValue', null);
		$vArguments["label"]				= array('$mValue', null);
		$vArguments["entity"]				= array('$mValue', null);
		$vArguments["title"]				= array('$mValue', null);
		$vArguments["filepath"]				= array('$mValue', null);
		$vArguments["fields"]				= array('(array)$mValue', null);
		$vArguments["run"]					= array('(boolean)$mValue', false);
		$vArguments["nestdata"]				= array('$this->SetNestData($mValue)', null);
		$vArguments["left"]					= array('(int)$mValue', 0);
		$vArguments["top"]					= array('(int)$mValue', 0);
		$vArguments["collapsed"]			= array('(int)$mValue', 0);
		$vArguments["canvas_width"]			= array('(int)$mValue', 1800);
		$vArguments["canvas_height"]		= array('(int)$mValue', 900);
		$vArguments["gui_part"]				= array('$mValue', "table");
		
		return $vArguments;
	}

	final protected function __declareAttributes__() {
		$vAttributes						= array();
		$vAttributes["object"]				= null;
		$vAttributes["objtype"]				= null;
		$vAttributes["sql"]					= null;
		return $vAttributes;
	}

	final protected function __declareVariables__() {
		// types
		$aTypes = array(
			array("label"=>"DATE", "value"=>"DATE"),
			array("label"=>"DECIMAL", "value"=>"DECIMAL"),
			array("label"=>"INTEGER", "value"=>"INTEGER"),
			array("label"=>"TEXT", "value"=>"TEXT"),
			array("label"=>"TIMESTAMP", "value"=>"TIMESTAMP"),
			array("label"=>"VARCHAR", "value"=>"VARCHAR")
		);

		// fields
		$aFields = array(
			"text" => array("alias"=>"text", "type"=>"TEXT", "length"=>"", "default"=>"NULL", "attrs"=>"--", "index"=>"--", "null"=>true),
			"code" => array("alias"=>"code", "type"=>"VARCHAR", "length"=>"16", "default"=>"NULL", "index"=>"UNIQUE", "null"=>true),
			"name" => array("alias"=>"name", "type"=>"VARCHAR", "length"=>"64", "default"=>"NONE", "attrs"=>"--", "index"=>"--", "null"=>false)
		);

		// presets
		$aPresets = array("basic" => array("code"=>"code", "name"=>"name"));

		// preset fields
		$aPresetFields = array();

		// seteo final
		$this->aFields 			= $aFields;
		$this->aTypes 			= $aTypes;
		$this->aPresets			= $aPresets;
		$this->aPresetFields	= $aPresetFields;
		$this->bUpdate			= false;
		$this->bAlterField		= false;
		$this->bRegenerate		= false;
		$this->aAdd				= array();
		$this->aAlterTable		= array();
		$this->aAlterField		= array();
		$this->aLoadData		= array();
		$this->aLoadDataIndex	= array();
		$this->aNormalize		= array();
		$this->aStarred			= array();
	}

	final public function __init__() {
		self::errorMode("print");
	}

	protected function SetNestData($sNestFile) {
		$sNestFile = self::call()->clearPath($sNestFile);
		$sNestFile = self::call()->sandboxPath($sNestFile);
		if(file_exists($sNestFile)) {
			$sJsonNest = file_get_contents($sNestFile);
			$aJsonNest = json_decode($sJsonNest, true);
			if(is_array($aJsonNest) && count($aJsonNest)) {
				if(isset($aJsonNest["types"])) { $aTypes = $aJsonNest["types"]; }
				if(isset($aJsonNest["fields"])) { $aFields = array_merge($this->aFields, $aJsonNest["fields"]); }
				if(isset($aJsonNest["presets"])) { $aPresets = array_merge($this->aPresets, $aJsonNest["presets"]); }
				if(isset($aJsonNest["presetfields"])) { $aPresetFields = array_merge($this->aPresetFields, $aJsonNest["presetfields"]); }
			}

			$this->aTypes 			= $aTypes;
			$this->aFields 			= $aFields;
			$this->aPresets			= $aPresets;
			$this->aPresetFields	= $aPresetFields;
		}		
	}

	public function add() {
		if($this->attribute("objtype")=="view") {
			self::errorMessage($this->object, 1012); // invalid action on a view
			return false;
		}

		list($sField,$mType,$sAfter) = $this->getarguments("field,type,after", func_get_args());
		$sObject = $this->attribute("object");

		if(is_array($sField) && count($sField)) {
			foreach($sField as $aField) {
				call_user_func_array(array($this, "add"), $aField);
			}
			return $this;
		}

		// $bNew = (!isset($this->owl["tables"][$sObject][$sField]));
		$sField = $this->FormatName($sField);
		if($this->bAlterField===true && is_array($mType)) {
			$sOldField = $sField;
			if(isset($mType["name"])) { $sField = $mType["name"]; }
			if(!isset($mType["label"])) { $mType["label"] = $sField; }

			$aOldType = $this->owl["def"][$sObject][$sOldField];
			$mType["oldname"] = $sOldField;
			$mType["oldindex"] = $aOldType["index"];

			if(!(isset($mType["type"]) || isset($mType["alias"]))) {
				$mType = array_merge($aOldType, $mType);
				if(isset($this->owl["joins"][$sObject])) {
					foreach($this->owl["joins"][$sObject] as &$sJoinField) {
						$aJoinField = explode(":", $sJoinField);
						if($aJoinField[0]==$sOldField) { $sJoinField = $sField.":".$aJoinField[1]; }
					}
					unset($sJoinField);
				}
			} else {
				$this->DefJoins($sObject, $sField);

				if(isset($mType["type"]) && $mType["type"][0]=="@") { // @tabla OR @tabla-padre cuando es pid
					$this->DefJoins($sObject, $sField, $mType["type"]);
					$mType = array_merge($mType, $this->aFields["fk"]);
				}
			}

			if($sAfter===true) {
				foreach($this->owl["tables"][$sObject] as $sFieldName => $v) {
					if($sFieldName==$sOldField) { break; }
					$sAfter = $sFieldName;
				}
			}

			unset($this->owl["tables"][$sObject][$sOldField], $this->owl["def"][$sObject][$sOldField]);
		}

		$sLabel = (isset($mType["label"])) ? $mType["label"] : $sField;
		if(strpos($sField, ":")!==false) {
			$aField = explode(":", $sField, 2);
			$sField = $aField[0];
			$sLabel = $aField[1];
		}

		if($sField=="id" || $sField=="imya" || $sField=="state") { return $this; }

		if($sField===null || $sObject===null) {
			self::errorMessage($this->object, 1005); // empty object or field name
			return false;
		} else if(isset($this->owl["tables"][$sObject][$sField]) && $this->bAlterField===false) {
			self::errorMessage($this->object, 1006, $sField); // field alredy exists
			return false;
		}

		if(is_array($mType)) {
			if(array_key_exists("type", $mType) && $mType["type"][0]=="@") { // @tabla OR @tabla-padre cuando es pid
				$this->DefJoins($sObject, $sField, $mType["type"]);
				$aType = $this->aFields["fk"];
			} else {
				if(array_key_exists("default", $mType)) {
					if(strtolower($mType["default"])=="now") {
						$mType["default"] = "CURRENT_TIMESTAMP";
					} else if(strtolower($mType["default"])=="null" || $mType["default"]===null) {
						$mType["default"] = "NULL";
						$mType["null"] = true;
					} else {
						$mType["default"] = "'".addslashes($mType["default"])."'";
					}
				}

				if(isset($mType["label"])) { $sLabel = $mType["label"]; }
				if(isset($mType["alias"], $this->aFields[strtolower($mType["alias"])])) {
					$aType = array_merge($this->aFields[strtolower($mType["alias"])], $mType);
				} else {
					$aType = array_merge($this->aFields["varchar"], $mType);
				}
			}
		} else if(is_string($mType)) {
			if(isset($this->aFields[$mType])) {
				$aType = $this->aFields[strtolower($mType)];
			} else if($mType[0]=="@") { // @tabla OR @tabla-padre cuando es pid
				$this->DefJoins($sObject, $sField, $mType);
				$aType = $this->aFields["fk"];
			} else {
				$aType = $this->aFields["varchar"];
			}
		} else {
			$aType = $this->aFields["varchar"];
		}

		if(!isset($aType["label"])) { $aType["label"] = $sLabel; }

		if($aType["default"]==NGL_NULL && (strtolower($aType["type"])=="enum" || strtolower($aType["type"])=="enum")) {
			$aType["default"] = explode("','", $aType["length"]);
			$aType["default"] = "'".substr($aType["default"][0], 1)."'";
		}

		if($sAfter===true) {
			$this->owl["tables"][$sObject][$sField] = $sField;
			$this->owl["def"][$sObject][$sField] = $aType;
		} else {
			self::call()->arrayInsert($this->owl["tables"][$sObject], $sAfter, array($sField=>$sField));
			self::call()->arrayInsert($this->owl["def"][$sObject], $sAfter, array($sField=>$aType));
		}

		if($this->bUpdate) {
			if(!isset($this->aAlterField[$sObject])) { $this->aAlterField[$sObject] = array(); }
			if(!isset($this->aAlterField[$sObject][$sField])) { $this->aAlterField[$sObject][$sField] = array(); }
			$this->aAlterField[$sObject][$sField][] = ($this->bAlterField===false) ? $sAfter : (($sField==$sOldField) ? "@MODIFY" : "@CHANGE");

			if(isset($sOldField) && $sField!=$sOldField && isset($this->owl["nest"]["objects"][$sObject]["starred"][$sOldField])) {
				unset($this->owl["nest"]["objects"][$sObject]["starred"][$sOldField]);
				$this->owl["nest"]["objects"][$sObject]["starred"][$sField] = $sField;
				$this->aStarred[$sObject] = true;
			}
		}

		return $this;
	}

	public function alter() {
		list($sField,$mType) = $this->getarguments("field,type", func_get_args());

		$this->bAlterField = true;
		$sField = $this->FormatName($sField);
		if(is_string($mType)) {
			if($mType[0]!="@") {
				$mType = array("name"=>$mType);
			} else {
				$mType = array("name"=>$sField, "type"=>$mType);
			}
		}

		$return = call_user_func_array(array($this, "add"), array($sField, $mType));
		$this->bAlterField = false;
		return $return;
	}

	public function move() {
		list($sObject,$sField,$sAfter) = $this->getarguments("entity,field,after", func_get_args());
		$sObject = $this->FormatName($sObject);
		$aFields = $this->owl["tables"][$sObject];
		if(!isset($aFields[$sAfter])) { $sAfter = true; }
		$aNewOrder = array();
		foreach($aFields as $sFieldKey => $sLabel) {
			if($sFieldKey!==$sField) { $aNewOrder[$sFieldKey] = $sLabel; }
			if($sFieldKey===$sAfter) { $aNewOrder[$sField] = $aFields[$sField]; }
		}
		if($sAfter===true) { $aNewOrder[$sField] = $aFields[$sField]; }

		$this->owl["tables"][$sObject] = $aNewOrder;
		return $this;
	}

	public function check() {
		list($sObject) = $this->getarguments("entity", func_get_args());
		return (isset($this->owl["tables"][$sObject]) || isset($this->owl["views"][$sObject]));
	}

	public function chtitle() {
		list($sTitle) = $this->getarguments("title", func_get_args());
		$sObject = $this->attribute("object");

		if($sObject==null) {
			self::errorMessage($this->object, 1001); // empty object name
			return false;
		} else if(!isset($this->owl["tables"][$sObject])) {
			self::errorMessage($this->object, 1004, $sObject); // object doesn't exists
			return false;
		}
		if($sTitle!==null) { $this->owl["titles"][$sObject] = $sTitle; }

		return $this;
	}

	public function create() {
		list($sObject, $sTitle, $aFields) = $this->getarguments("entity,title,fields", func_get_args());

		if($sObject==null) {
			self::errorMessage($this->object, 1001); // empty object name
			return false;
		} else if(isset($this->owl["tables"][$sObject])) {
			self::errorMessage($this->object, 1002, $sObject); // object alredy exists
			return false;
		}

		$sObject = $this->FormatName($sObject);
		$this->owl["tables"][$sObject]	= array();
		$this->owl["titles"][$sObject]	= ($sTitle!==null) ? $sTitle : $sObject;
		$this->owl["nest"]["objects"][$sObject]	= array("left"=>0, "top"=>0);
		$this->owl["def"][$sObject]		= array();
		$this->SetObject($sObject);

		if(is_array($aFields) && count($aFields)) {
			foreach($aFields as $sField => $mType) {
				if(is_int($sField)) {
					$sField = $mType;
					$sType = null;
				}
				$this->add($sField, $mType);
			}
		}

		if($this->bUpdate) { $this->aAdd[$sObject] = true; }
		return $this;
	}

	public function describe() {
		list($sAbout) = $this->getarguments("about", func_get_args());
		$sAbout = strtolower($sAbout);
		$sObject = $this->attribute("object");
		if($sObject===null) { return $this->describeall(); }

		if($sAbout=="fields") { return array_values($this->owl["tables"][$sObject]); }
		if($sAbout=="structure") { return $this->owl["def"][$sObject]; }

		$aFrom = $aRelations = array();
		$aSelect = array("`".$sObject."`.`id` AS '__id__'", "`".$sObject."`.`imya` AS '__imya__'");
		if(isset($this->owl["parents"][$sObject])) {
			$sParent = $this->owl["parents"][$sObject];
			$aRelations[] =  "TABLE `".$sObject."` CHILD OF `".$sParent."` [__parent]";
			$aSelect = array_merge($aSelect, $this->DescribeColumns($sObject, $sObject), $this->DescribeColumns($sParent, $sParent));
			$aFrom[] = $sObject;
			$aFrom[] = "LEFT JOIN `".$sParent."` ".$sParent." ON `".$sParent."`.`id` = `".$sObject."`.`pid`";
			if(isset($this->owl["joins"][$sParent])) {
				foreach($this->owl["joins"][$sParent] as $sTable) {
					$aTable = explode(":", $sTable);
					$sAlias = $sParent."_".$aTable[1];
					$aRelations[] = "-- PARENT JOINED TO `".$aTable[1]."` AS '".$sAlias."' [__parent_".$aTable[1]."] USING `".$aTable[0]."` FIELD";
	
					$aSelect = array_merge($aSelect, $this->DescribeColumns($aTable[1], $sAlias));
					$aFrom[] = "LEFT JOIN `".$aTable[1]."` ".$sAlias." ON `".$sAlias."`.`id` = `".$sParent."`.`".$aTable[0]."`";
				}
			}
		} else {
			$aRelations[] = "MAIN TABLE `".$sObject."`";
			$aSelect = $this->DescribeColumns($sObject, $sObject);
			$aFrom[] = $sObject;
		}

		if(isset($this->owl["children"][$sObject])) {
			foreach($this->owl["children"][$sObject] as $sChildren) {
				$aRelations[] = "-- PARENT OF `".$sChildren."`";

				$aSelect = array_merge($aSelect, $this->DescribeColumns($sChildren, $sChildren));
				$aFrom[] = "LEFT JOIN `".$sChildren."` ON `".$sChildren."`.`pid` = `".$sObject."`.`id`";

				if(isset($this->owl["joins"][$sChildren])) {
					foreach($this->owl["joins"][$sChildren] as $sTable) {
						$aTable = explode(":", $sTable);
						$sAlias = $sObject."_".$aTable[1];
						$aRelations[] = "------ JOINED TO `".$aTable[1]."` AS '".$sAlias."' USING `".$aTable[0]."` FIELD";

						$aSelect = array_merge($aSelect, $this->DescribeColumns($aTable[1], $sAlias));
						$aFrom[] = "LEFT JOIN `".$aTable[1]."` ".$sAlias." ON `".$sAlias."`.`id` = `".$sChildren."`.`".$aTable[0]."`";
					}
				}
			}
		}

		if(isset($this->owl["joins"][$sObject])) {
			foreach($this->owl["joins"][$sObject] as $sTable) {
				$aTable = explode(":", $sTable);
				$sAlias = $sObject."_".$aTable[1];
				$aRelations[] = "---- JOINED TO `".$aTable[1]."` AS '".$sAlias."' USING `".$aTable[0]."` FIELD";

				$aSelect = array_merge($aSelect, $this->DescribeColumns($aTable[1], $sAlias));
				$aFrom[] = "LEFT JOIN `".$aTable[1]."` ".$sAlias." ON `".$sAlias."`.`id` = `".$sObject."`.`".$aTable[0]."`";
			}
		}

		$sRelations = implode("\n", $aRelations);
		if($sAbout=="relations") { return $sRelations; }
		
		$sView = "CREATE OR REPLACE VIEW `view_".$sObject."` AS (\n";
		$sView .= "\tSELECT\n\t\t".implode(",\n\t\t", $aSelect)."\n";
		$sView .= "\tFROM\n\t\t".implode("\n\t\t", $aFrom)."\n";
		$sView .= ");";
		if($sAbout=="view") { return $sView; }

		$bView = (!isset($this->owl["tables"][$sObject]) && isset($this->owl["views"][$sObject])) ? true : false;
		$aDescribe = array(
			"title" => (!$bView) ? (isset($this->owl["titles"][$sObject]) ? $this->owl["titles"][$sObject] : $sObject) : $this->owl["views"][$sObject]["title"],
			"fields" => (!$bView) ? $this->owl["tables"][$sObject] : array_combine(array_keys($this->owl["views"][$sObject]["fields"]), array_keys($this->owl["views"][$sObject]["fields"])),
			"relationship" => "\n\n".$sRelations."\n\n",
			"view" => "\n\n".$sView."\n\n",
			"structure" => (isset($this->owl["def"][$sObject])) ? $this->owl["def"][$sObject] : $this->owl["views"][$sObject]["fields"],
			"foreignkeys" => (isset($this->owl["foreignkeys"][$sObject]) ? $this->owl["foreignkeys"][$sObject] : null),
			"parent" => (isset($this->owl["parents"][$sObject]) ? $this->owl["parents"][$sObject] : null),
			"children" => (isset($this->owl["children"][$sObject]) ? $this->owl["children"][$sObject] : null),
			"joins" => (isset($this->owl["joins"][$sObject]) ? $this->owl["joins"][$sObject] : null),
			"validator" => (isset($this->owl["validator"][$sObject]) ? $this->owl["validator"][$sObject] : null)
		);

		if(isset($aDescribe[$sAbout])) { return $aDescribe[$sAbout]; }
		return $aDescribe;
	}

	public function describeall() {
		list($bDer) = $this->getarguments("der", func_get_args());
		return ($bDer) ? $this->Structure() : $this->owl;
	}

	public function drop() {
		if($this->attribute("objtype")=="view") {
			self::errorMessage($this->object, 1012); // invalid action on a view
			return false;
		}

		$sObject = $this->attribute("object");
		foreach($this->owl["children"] as $sChildren => $aChildren) {
			if(isset($aChildren[$sObject])) { unset($this->owl["children"][$sChildren][$sObject]); }
			if(!count($this->owl["children"][$sChildren])) { unset($this->owl["children"][$sChildren]); }
		}

		foreach($this->owl["joins"] as $sJoin => $aJoin) {
			if($sJoin==$sObject) { unset($this->owl["joins"][$sObject]); continue; }
			foreach($aJoin as $nJoin => $sJoinText) {
				if(strpos($sJoinText, ":".$sObject.":")) { unset($this->owl["joins"][$sJoin][$nJoin]); }
			}
			if(!count($this->owl["joins"][$sJoin])) { unset($this->owl["joins"][$sJoin]); }
		}

		unset(
			$this->owl["tables"][$sObject],
			$this->owl["nest"]["objects"][$sObject],
			$this->owl["titles"][$sObject],
			$this->owl["def"][$sObject],
			$this->owl["foreignkeys"][$sObject],
			$this->owl["children"][$sObject],
			$this->owl["validator"][$sObject]
		);
		foreach($this->owl["parents"] as $sChild => $sParent) {
			if($sObject==$sParent) { unset($this->owl["parents"][$sChild]); }
		}

		$sNewName = "dropped_".$sObject."_".date("YmdHis")."_".self::call()->unique(8);
		$this->aAlterTable[] = "`".$sObject."` TO `".$sNewName."`";
		$this->attribute("object", null);
		return $this;
	}

	public function createCode() {
		if($this->attribute("objtype")=="view") {
			self::errorMessage($this->object, 1012); // invalid action on a view
			return false;
		}

		$sObject = $this->attribute("object");
		if(isset($this->owl["def"][$sObject])) {
			return $this->CreateTableStructure($sObject, $this->owl["def"][$sObject], true);
		}

		return false;
	}

	public function createNestCode() {
		if($this->attribute("objtype")=="view") {
			self::errorMessage($this->object, 1012); // invalid action on a view
			return false;
		}

		$sObject = $this->attribute("object");
		if(isset($this->owl["def"][$sObject])) {
			$aTableFields = $this->owl["def"][$sObject];
			if(isset($this->owl["parents"][$sObject])) { $aTableFields["pid"] = array("name"=>"pid", "type"=>"@".$this->owl["parents"][$sObject]); }
			if(isset($this->owl["joins"][$sObject])) {
				foreach($this->owl["joins"][$sObject] as $sJoin) {
					$aJoin = explode(":", $sJoin);
					$aTableFields[$aJoin[0]] = array("name"=>$aJoin[0], "type" => "@".$aJoin[1]);
				}
			}

			$sNestCode = '-$: create ["'.$sObject.'","'.$this->owl["titles"][$sObject].'"]'."\n";
			foreach($aTableFields as $sField => $aField) {
				$sNestCode .= '-$: add ["'.$sField.'", '.json_encode($aField).']'."\n";
			}

			return $sNestCode;
		}
	}

	public function regenerate() {
		$this->bRegenerate = true;
		$bCore = $this->argument("core");
		$this->args("core", true);
		$sCode = $this->generate(false);
		$this->bRegenerate = false;
		$this->args("core", $bCore);
		return $sCode;
	}

	public function generate() {
		list($bRun) = $this->getarguments("run", func_get_args());
		$db = $this->argument("db");
		$bCreateStructure = $this->argument("core");
		if($db===null) { self::errorMessage($this->object, 1009); } // undefined DB driver

		$aDbConfig = array();
		$aDbConfig["debug"] = $db->debug;
		$aDbConfig["insert_mode"] = $db->insert_mode;
		$aDbConfig["check_colnames"] = $db->check_colnames;

		$db->debug = true;
		$db->insert_mode = "REPLACE";
		$db->check_colnames = false;
		$db->connect();

		$aOWL = $this->owl;

		// fix campos
		$aBasic = array("id"=>"id", "imya"=>"imya", "state"=>"state");
		foreach($aOWL["tables"] as $sTable => &$aTable) {
			$aTable = array_merge($aBasic, $aTable);
			foreach($aOWL["def"][$sTable] as $sField => $aField) {
				$aTable[$sField] = (isset($aField["label"])) ? $aField["label"] : $sField;
			}
		}
		unset($aTable);
		ksort($aOWL["tables"]);

		// fix joins
		if(isset($aOWL["joins"])) {
			foreach($aOWL["joins"] as $sTable => $aTable) {
				$aTableJoins = array();
				foreach($aTable as $sField) {
					$aTableJoins[substr($sField, 0, strpos($sField, ":"))] = $sField;
				}
				$aOWL["joins"][$sTable] = $aTableJoins;
				asort($aOWL["joins"][$sTable]);
			}
		}

		// fix parents/children
		if(isset($aOWL["children"])) {
			$aNewChildren = array();
			foreach($aOWL["children"] as $sParent => $aChildren) {
				$aNewChildren[$sParent] = array();
				foreach($aChildren as $mKey => $sChildren) {
					$aNewChildren[$sParent][$sChildren] = $sChildren;
					$aOWL["parents"][$sChildren] = $sParent;
				}
			}
			$aOWL["children"] = $aNewChildren;
		}

		foreach(array_keys($aOWL["tables"]) as $sTable) {
			if(!isset($aOWL["titles"][$sTable])) { $aOWL["titles"][$sTable] = $sTable; }
		}

		$aJSON = $aOWL;
		foreach($aJSON["def"] as &$aJsonTable) {
			foreach($aJsonTable as &$aJsonField) {
				unset($aJsonField["oldname"], $aJsonField["oldindex"]);
			}
		}
		unset($aJsonTable, $aJsonField);

		// sort
		ksort($aJSON["tables"]);
		ksort($aJSON["nest"]["objects"]);
		ksort($aJSON["titles"]);
		ksort($aJSON["views"]);
		ksort($aJSON["def"]);
		ksort($aJSON["parents"]);
		ksort($aJSON["children"]);
		ksort($aJSON["joins"]);
		ksort($aJSON["validator"]);

		$sJSON = self::call("shift")->convert($aJSON, "array-json");

		$sJSONCompact = self::call("shift")->jsonformat($sJSON, true);
		if($this->bUpdate==false || $bCreateStructure) {
			$sSQLStructure = $this->CreateStructure();
		} else {
			$sSQLStructure = "";
		}

		// RENAME / DROP TABLE
		if(count($this->aAlterTable)) {
			$sSQLStructure .= "RENAME TABLE ".implode(", ", $this->aAlterTable).";\n\n";
		}

		if($this->bRegenerate) { $this->aStarred = array(); }
		foreach($aOWL["def"] as $sTable => $aTable) {
			$sSQLStructure .= $this->CreateTableStructure($sTable, $aTable, $this->bRegenerate);
			if($this->bRegenerate) {
				if(array_key_exists("starred", $this->owl["nest"]["objects"][$sTable])) {
					$this->aStarred[$sTable] = true;
				}
			}
		}
		
		if(count($this->aStarred)) {
			$sSQLStructure .= "-- STARRED FIELDS --\n";
			foreach($this->aStarred as $sStarred => $w) {
				$sFullTextFields = "`".implode("`, `", $this->owl["nest"]["objects"][$sStarred]["starred"])."`";
				$sSQLStructure .= "CALL func.drop_index(DATABASE(), '".$sStarred."', 'globalsearch');\n";
				$sSQLStructure .= "ALTER TABLE `".$sStarred."` ADD FULLTEXT INDEX `globalsearch` (".$sFullTextFields.");\n\n";
			}
		}

		if(!$this->bRegenerate) {
			if(count($this->aNormalize)) {
				$sSQLStructure .= "\n-- -----------------------------------------------------------------------------\n\n";
				$sSQLStructure .= "-- NORMALIZE --\n";
				foreach($this->aNormalize as $sNewObject => $aNormalize) {
					$sSQLStructure .= "-- `".$aNormalize[0]."`.`".$aNormalize[1]."` TO `".$sNewObject."` --\n";
				}
			}

			if(count($this->aLoadDataIndex)) {
				$sSQLStructure .= "\n-- -----------------------------------------------------------------------------\n\n";
				$sSQLStructure .= "-- LOAD DATA FROM FILES --\n";
				foreach($this->aLoadDataIndex as $sLoadDataIndexName => $sLoadDataIndexFile) {
					$sSQLStructure .= "-- ".$sLoadDataIndexFile." INTO `".$sLoadDataIndexName."` --\n";
				}
			}
		}
		
		// ESTRUCTURA
		$sJSONCompact = $db->escape($sJSONCompact);
		$sSQL = "\n-- -----------------------------------------------------------------------------\n\n";
		$sSQL .= "-- SAVE OWL STRUCTURE ON `__ngl_sentences__` --\n";
		$sSQL .= "REPLACE INTO `__ngl_sentences__` SELECT CONCAT('owl_', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')) AS 'name', 'structure' AS 'type', `sentence`, `notes` FROM `__ngl_sentences__` WHERE `name` = 'owl';\n";
		$sSQL .= $db->insert("__ngl_sentences__", array("name"=>"owl", "type"=>"structure", "sentence"=>$sJSONCompact)).";\n";
		$sSQL .= "\n\n";

		$sLogSQL = $sSQLStructure."\n\n".$sSQL;
		
		$sSQL .= "-- EMPTIES THE TABLE `__ngl_owl_structure__`;\n";
		$sSQL .= "TRUNCATE TABLE `__ngl_owl_structure__`;\n\n";
		
		// COLUMNAS
		if(isset($aOWL["tables"])) {
			$sSQL .= "-- BEGIN COLUMNS --\n";
			foreach($aOWL["tables"] as $sTable => $aColumns) {
				$sColumns = '["'.implode('","', array_keys($aColumns)).'"]';
				$sColumns = $db->escape($sColumns);
				$sSQL .= $db->insert("__ngl_owl_structure__", array("name"=>$sTable, "columns"=>$sColumns)).";\n";
			}
		}
		if(isset($aOWL["views"])) {
			foreach($aOWL["views"] as $sTable => $aView) {
				$aColumns = array_keys($aView["fields"]);
				$sColumns = '["'.implode('","', $aColumns).'"]';
				$sColumns = $db->escape($sColumns);
				$sSQL .= $db->insert("__ngl_owl_structure__", array("name"=>$sTable, "columns"=>$sColumns)).";\n";
			}
			$sSQL .= "-- END COLUMNS --\n\n";
		}
		
		// FOREIGNKEYS
		if(isset($aOWL["foreignkeys"])) {
			$sSQL .= "-- BEGIN FOREIGNKEYS --\n";
			foreach($aOWL["foreignkeys"] as $sTable => $aKeys) {
				$aForeignkeys = array("fields"=>array(), "tables"=>array());
				foreach($aKeys as $sRef) {
					$sRef = str_replace(".", ":", $sRef);
					$aRef = explode(":", $sRef, 3);
					$aForeignkeys["fields"][$aRef[0]] = $aRef[0];
		
					if(!isset($aForeignkeys["tables"][$aRef[1]])) { $aForeignkeys["tables"][$aRef[1]] = array(); }
					$aForeignkeys["tables"][$aRef[1]][] = $aRef[2];
				}
				$aForeignkeys["fields"] = array_values($aForeignkeys["fields"]);
				$sForeignkeys = json_encode($aForeignkeys);
		
				$sForeignkeys = $db->escape($sForeignkeys);
				$sSQL .= $db->update("__ngl_owl_structure__", array("foreignkey"=>$sForeignkeys), "`name`='".$sTable."'").";\n";
			}
			$sSQL .= "-- END FOREIGNKEYS --\n\n";
		}
		
		// JOINS
		$aJoins = array();
		if(isset($aOWL["joins"])) {
			foreach($aOWL["joins"] as $sTable => $aReferences) {
				if(!isset($aJoins[$sTable])) { $aJoins[$sTable] = array("joins"=>array(), "children"=>array()); }
				foreach($aReferences as $sRef) {
					// $aLabels = array("using","name","alias");
					$aLabels = array("using","name", "field");
					$aCross = explode(":", $sRef, 3);
					if(!isset($aCross[2])) { unset($aLabels[2]); }
					$aJoins[$sTable]["joins"][] = array_combine($aLabels, $aCross);
				}
			}
		}
		
		if(isset($aOWL["children"])) {
			foreach($aOWL["children"] as $sTable => $aChildren) {
				if(!isset($aJoins[$sTable])) { $aJoins[$sTable] = array("joins"=>array(), "children"=>array(), "parent"=>""); }
				foreach($aChildren as $sChildren) {
					$aJoins[$sTable]["children"][] = array("name" => $sChildren);
					if(!isset($aJoins[$sChildren])) { $aJoins[$sChildren] = array("joins"=>array(), "children"=>array(), "parent"=>""); }
					$aJoins[$sChildren]["parent"] = $sTable;
				}
			}
		}
		
		if(count($aJoins)){
			$sSQL .= "-- BEGIN JOINS-CHILDREN --\n";
			foreach($aJoins as $sTable => $aJoin) {
				if(!count($aJoin["joins"])) { unset($aJoin["joins"]); }
				if(!count($aJoin["children"])) { unset($aJoin["children"]); }
				$sJoins = json_encode($aJoin);
				$sJoins = $db->escape($sJoins);
				$sSQL .= $db->update("__ngl_owl_structure__", array("relationship"=>$sJoins), "`name`='".$sTable."'").";\n";
			}
			$sSQL .= "-- END JOINS-CHILDREN --\n\n";
		}
		
		// VALIDATOR
		if(isset($aOWL["validator"])) {
			$sSQL .= "-- BEGIN VALIDATOR --\n";
			foreach($aOWL["validator"] as $sTable => $aFields) {
				$aValidator = array();
				foreach($aFields as $sField => $aRules) {
					$aField = array();
					$aField["type"] = $aRules["type"];
					
					foreach($aRules["options"] as $sOption => $mValue) {
						if($mValue==="") { unset($aRules["options"][$sOption]); }
					}
					if(count($aRules["options"])) { $aField["options"] = $aRules["options"]; }
					
					if(isset($aRules["rule"])) {
						foreach($aRules["rule"] as $sRule => $mValue) {
							if($mValue!=="") { $aField[$sRule] = $mValue; }
						}
					}
		
					$aValidator[$sField] = $aField;
				}
		
				$sValidator = json_encode($aValidator);
				$sValidator = $db->escape($sValidator);
				$sSQL .= $db->update("__ngl_owl_structure__", array("validate_insert"=>$sValidator), "`name`='".$sTable."'").";\n";
				$sSQL .= $db->update("__ngl_owl_structure__", array("validate_update"=>$sValidator), "`name`='".$sTable."'").";\n";
			}
			$sSQL .= "-- END VALIDATOR --\n";
		}

		$db->debug = $aDbConfig["debug"];
		$db->insert_mode = $aDbConfig["insert_mode"];
		$db->check_colnames = $aDbConfig["check_colnames"];
		$this->attribute("sql", "use `".$this->db->base."`;\n\n".$sSQLStructure."\n\n".$sSQL);

		if($bRun) {
			$db->mquery($this->attribute("sql"));

			// carga de datos del createfromfile
			if(count($this->aLoadData)) {
				$owl = self::call("owl")->connect($db);
				foreach($this->aLoadData as $sObjectToLoad => $aLoadData) {
					$owl->select($sObjectToLoad);
					$nCols = count($aLoadData["fields"]);
					foreach($aLoadData["data"] as $aRow) {
						$aRow = array_slice($aRow, 0, $nCols);
						$sRow = trim(implode("", $aRow));
						if($sRow=="") { break; }
						$owl->insert(array_combine($aLoadData["fields"], $aRow));
					}
				}
			}

			// normalizaciones
			if(count($this->aNormalize)) {
				$owl = self::call("owl")->connect($db);
				foreach($this->aNormalize as $sNewObject => $aNormalize) {
					$vals = $db->query("SELECT DISTINCT `".$aNormalize[1]."_".$aNormalize[2]."` FROM `".$aNormalize[0]."` ORDER BY 1");
					if($vals->rows()) {
						$owl->select($sNewObject);
						while($sVal = $vals->get($aNormalize[1]."_".$aNormalize[2])) {
							$nId = $owl->insert(array("nombre"=>$sVal));
							$db->query("UPDATE `".$aNormalize[0]."` SET `".$aNormalize[1]."` = '".$nId."' WHERE `".$aNormalize[1]."_".$aNormalize[2]."` = '".$sVal."'");
						}
					}
				}
			}

			self::log("nest.log", "-- ".date("Y-m-d H:i:s")." ---------------------------------------------------------\n".$sLogSQL);

			return "ok";
		} else {
			return utf8_encode($this->attribute("sql"));
		}
	}

	public function join() {
		list($sUsing,$sWith,$sField) = $this->getarguments("using,entity,field", func_get_args());

		$sObject = $this->attribute("object");
		$sField = $this->FormatName($sField);
		$sWith = $this->FormatName($sWith);
		$sUsing = $this->FormatName($sUsing);
		if($sField===null || $sObject===null || $sUsing===null) {
			self::errorMessage($this->object, 1005); // field name, empty object or using name
			return false;
		} else if(!isset($this->owl["tables"][$sObject]) && !isset($this->owl["views"][$sWith])) {
			self::errorMessage($this->object, 1011, $sField); // with object dosent exists
			return false;
		}

		if(!isset($this->owl["joins"][$sObject])) { $this->owl["joins"][$sObject] = array(); }
		$this->owl["joins"][$sObject][] = $sUsing.":".$sWith.":".$sField;

		if(!isset($this->owl["views"][$sWith]["joins"])) { $this->owl["views"][$sWith]["joins"] = array(); }
		$this->owl["views"][$sWith]["joins"][$sObject] = array($sUsing, $sField);

		return $this;
	}

	public function load() {
		list($mStructure,$db) = $this->getarguments("structure,db", func_get_args());

		if($db===null) { $db = self::call("mysql"); }
		if(!$db->connect()) {
			self::errorMessage($this->object, 1009);
			return false;
		}
		$this->args(array("db"=>$db));

		if(is_array($mStructure)) {
			$aStructure = $mStructure;
		} else if(is_string($mStructure)) {
			if($db->query("SHOW TABLES LIKE '__ngl_sentences__'")->rows()) {
				if(strtolower(substr($mStructure,0,3))=="owl") {
					$mStructure = preg_replace("/[^owl_0-9]/", "", $mStructure);
					$sentence = $db->query("SELECT `sentence` FROM `__ngl_sentences__` WHERE `name` = '".$mStructure."'");
					if($sentence && $sentence->rows()) {
						$mStructure = $sentence->get("sentence");
					} else {
						self::errorMessage($this->object, 1010); // owl sentence
					}
				}
				$aStructure = json_decode($mStructure, true);
			}
		}

		$aDefault = array(
			"tables" => array(),
			"nest" => array("canvas"=>array("width"=>"1024", "height"=>"768"), "objects"=>array()),
			"titles" => array(),
			"views" => array(),
			"def" => array(),
			"foreignkeys" => array(),
			"parents" => array(),
			"children" => array(),
			"joins" => array(),
			"validator" => array()
		);

		if(!isset($aStructure)) {
			$aStructure = $aDefault;
		} else {
			$aStructure = array_merge($aDefault, $aStructure);
			if(isset($aStructure["bee"])) {
				$aStructure["nest"] = array("canvas"=>array(), "objects" => $aStructure["bee"]);
				unset($aStructure["bee"]);
			}
			$this->bUpdate = true;
		}

		// sort
		ksort($aStructure["tables"]);
		ksort($aStructure["nest"]["objects"]);
		ksort($aStructure["titles"]);
		ksort($aStructure["views"]);
		ksort($aStructure["def"]);
		ksort($aStructure["parents"]);
		ksort($aStructure["children"]);
		ksort($aStructure["joins"]);
		ksort($aStructure["validator"]);

		$this->owl = $aStructure;
		if(!count($aStructure["nest"]["canvas"])) {
			$this->canvas();
		}

		return $this;
	}

	public function position() {
		list($sObject,$nLeft,$nTop) = $this->getarguments("entity,left,top", func_get_args());
		$sObject = $this->FormatName($sObject);
		if(isset($this->owl["tables"][$sObject]) || isset($this->owl["views"][$sObject])) {
			if(!isset($this->owl["nest"]["objects"][$sObject])) { $this->owl["nest"]["objects"][$sObject] = array(); }
			$this->owl["nest"]["objects"][$sObject]["left"] = $nLeft;
			$this->owl["nest"]["objects"][$sObject]["top"] = $nTop;
		}
		return $this;
	}

	public function star() {
		list($sField) = $this->getarguments("field", func_get_args());
		$sObject = $this->attribute("object");

		$sField = $this->FormatName($sField);
		if(isset($this->owl["tables"][$sObject]) || isset($this->owl["views"][$sObject])) {
			if(!isset($this->owl["nest"]["objects"][$sObject]["starred"])) { $this->owl["nest"]["objects"][$sObject]["starred"] = array(); }
			if(isset($this->owl["nest"]["objects"][$sObject]["starred"][$sField])) {
				unset($this->owl["nest"]["objects"][$sObject]["starred"][$sField]);
				$this->aStarred[$sObject] = true;
			} else {
				$this->owl["nest"]["objects"][$sObject]["starred"][$sField] = $sField;
				$this->aStarred[$sObject] = true;
			}
		}
		return $this;
	}

	public function gui() {
		list($sGUI,$sField) = $this->getarguments("gui_part,field", func_get_args());
		$sObject = $this->attribute("object");

		$sField = $this->FormatName($sField);
		if(isset($this->owl["tables"][$sObject])) {
			if(!isset($this->owl["nest"]["objects"][$sObject]["gui"])) { $this->owl["nest"]["objects"][$sObject]["gui"] = array("table", "form"); }
			if(isset($this->owl["nest"]["objects"][$sObject]["gui"][$sGUI][$sField])) {
				unset($this->owl["nest"]["objects"][$sObject]["gui"][$sGUI][$sField]);
			} else {
				$this->owl["nest"]["objects"][$sObject]["gui"][$sGUI][$sField] = $sField;
			}
		}
		return $this;
	}

	public function collapse() {
		list($sObject,$nCollapsed) = $this->getarguments("entity,collapsed", func_get_args());
		$sObject = $this->FormatName($sObject);
		if(isset($this->owl["tables"][$sObject]) || isset($this->owl["views"][$sObject])) {
			if(!isset($this->owl["nest"]["objects"][$sObject])) { $this->owl["nest"]["objects"][$sObject] = array(); }
			$this->owl["nest"]["objects"][$sObject]["collapsed"] = $nCollapsed;
		}
		return $this;
	}

	public function canvas() {
		list($nWidth,$nHeight) = $this->getarguments("canvas_width,canvas_height", func_get_args());
		if(!isset($this->owl["nest"]["canvas"])) { $this->owl["nest"]["canvas"] = array(); }
		$this->owl["nest"]["canvas"]["width"] = $nWidth;
		$this->owl["nest"]["canvas"]["height"] = $nHeight;
		return $this;
	}

	public function preset() {
		list($sEntity, $sNewName, $sTitle) = $this->getarguments("entity,newname,title", func_get_args());
		$sEntity = $this->FormatName($sEntity);
		if($sNewName===null) { $sNewName = $sEntity; }
		if(isset($this->aPresets[$sEntity])) {
			if($sTitle===null) { $sTitle = $sNewName; }
			$this->create($sNewName, $sTitle, $this->aPresets[$sEntity]);
		}

		return $this;
	}
	
	public function presets() {
		$aPresets = $this->aPresets;
		ksort($aPresets);
		return $aPresets;
	}

	public function rem() {
		if($this->attribute("objtype")=="view") {
			self::errorMessage($this->object, 1012); // invalid action on a view
			return false;
		}

		list($mField) = $this->getarguments("field", func_get_args());
		if(is_array($mField)) {
			foreach($mField as $sField) {
				$this->rem($sField);
			}
			return $this;
		}

		$sObject = $this->attribute("object");
		if($mField===null || $sObject===null) {
			self::errorMessage($this->object, 1005, $sObject); // empty object or field name
			return false;
		} else if(!isset($this->owl["tables"][$sObject][$mField])) {
			self::errorMessage($this->object, 1008, $sObject.".".$mField); // field doesn't exists
			return false;
		}

		$mField = $this->FormatName($mField);
		unset($this->owl["tables"][$sObject][$mField], $this->owl["def"][$sObject][$mField]);
		if(isset($this->owl["joins"][$sObject])) {
			foreach($this->owl["joins"][$sObject] as $sIndex=>$sJoin) {
				if(strpos($sJoin, $mField.":")===0) {
					unset($this->owl["joins"][$sObject][$sIndex]);
				}
			}

			if(!count($this->owl["joins"][$sObject])) { unset($this->owl["joins"][$sObject]); }
		}

		if(!isset($this->aAlterField[$sObject])) { $this->aAlterField[$sObject] = array(); }
		if(!isset($this->aAlterField[$sObject][$mField])) { $this->aAlterField[$sObject][$mField] = array(); }
		$this->aAlterField[$sObject][$mField][] = "@DROP";

		if(isset($this->owl["nest"]["objects"][$sObject]["starred"][$mField])) {
			unset($this->owl["nest"]["objects"][$sObject]["starred"][$mField]);
		}

		return $this;
	}

	public function rename() {
		list($sNewName, $sTitle) = $this->getarguments("newname,title", func_get_args());

		$sObject = $this->attribute("object");
		$sNewName = $this->FormatName($sNewName);
		if($sObject==null || $sNewName==null) {
			self::errorMessage($this->object, 1001); // empty object name
			return false;
		} else if(!isset($this->owl["tables"][$sObject])) {
			self::errorMessage($this->object, 1004, $sObject); // object doesn't exists
			return false;
		} else if(isset($this->owl["tables"][$sNewName]) && $sObject!=$sNewName) {
			self::errorMessage($this->object, 1002, $sNewName); // object alredy exists
			return false;
		}

		$this->owl["tables"][$sNewName]							= $this->owl["tables"][$sObject];
		$this->owl["titles"][$sNewName]							= ($sTitle!==null) ? $sTitle : $this->owl["titles"][$sObject];
		if($sObject==$sNewName) { return $this; }

		$this->owl["def"][$sNewName]							= $this->owl["def"][$sObject];
		if(isset($this->owl["nest"]["objects"][$sObject])) {	$this->owl["nest"]["objects"][$sNewName]	= $this->owl["nest"]["objects"][$sObject]; }
		if(isset($this->owl["foreignkeys"][$sObject])) { 		$this->owl["foreignkeys"][$sNewName]		= $this->owl["foreignkeys"][$sObject]; }
		if(isset($this->owl["foreignkeys"][$sObject])) { 		$this->owl["foreignkeys"][$sNewName]		= $this->owl["foreignkeys"][$sObject]; }
		if(isset($this->owl["parents"][$sObject])) { 			$this->owl["parents"][$sNewName]			= $this->owl["parents"][$sObject]; }
		if(isset($this->owl["children"][$sObject])) { 			$this->owl["children"][$sNewName]			= $this->owl["children"][$sObject]; }
		if(isset($this->owl["joins"][$sObject])) { 				$this->owl["joins"][$sNewName]				= $this->owl["joins"][$sObject]; }
		if(isset($this->owl["validator"][$sObject])) { 			$this->owl["validator"][$sNewName]			= $this->owl["validator"][$sObject]; }
		if(isset($this->aAdd[$sObject])) {						$this->aAdd[$sNewName]						= true; }
		unset(
			$this->owl["tables"][$sObject],
			$this->owl["nest"]["objects"][$sObject],
			$this->owl["titles"][$sObject],
			$this->owl["def"][$sObject],
			$this->owl["foreignkeys"][$sObject],
			$this->owl["children"][$sObject],
			$this->owl["joins"][$sObject],
			$this->owl["validator"][$sObject],
			$this->aAdd[$sObject]
		);

		foreach($this->owl["parents"] as $sChild => $sParent) {
			if($sObject==$sParent) {
				$this->owl["parents"][$sChild] = $sNewName;
			}
		}

		foreach($this->owl["joins"] as $sJoinWith => &$aJoins) {
			foreach($aJoins as &$sJoin) {
				$aJoin = explode(":", $sJoin);
				if($aJoin[1]==$sObject) {
					$aJoin[1] = $sNewName;
					$sJoin = implode(":", $aJoin);
				}
			}
			unset($sJoin);
		}
		unset($aJoins);

		foreach($this->owl["children"] as &$aChildren) {
			foreach($aChildren as $sChildName) {
				if($sChildName==$sObject) {
					$aChildren[$sNewName] = $sNewName;
					unset($aChildren[$sObject]);
				}
			}
		}

		if(!isset($this->aAdd[$sNewName])) { $this->aAlterTable[] = "`".$sObject."` TO `".$sNewName."`"; }
		$this->SetObject($sNewName);
		return $this;
	}

	public function save() {
		return json_encode($this->owl);
	}

	public function twin() {
		list($sTwin, $sTitle) = $this->getarguments("newname,title", func_get_args());
		$sObject = $this->attribute("object");
		$sTwin = $this->FormatName($sTwin);
		$this->create($sTwin, $sTitle, $this->owl["def"][$sObject]);
		if(isset($this->owl["foreignkeys"][$sObject])) { $this->owl["foreignkeys"][$sTwin] = $this->owl["foreignkeys"][$sObject]; }
		if(isset($this->owl["joins"][$sObject])) {
			$aJoins = array();
			foreach($this->owl["joins"][$sObject] as $sJoin) {
				$aJoin = explode(":", $sJoin);
				if($sObject==$aJoin[1]) { $aJoin[1] = $sTwin; }
				$aJoin[2] = $sTwin."_".$aJoin[1];
				$aJoins[] = implode(":", $aJoin);
			}
			$this->owl["joins"][$sTwin] = $aJoins;
		}
		if(isset($this->owl["validator"][$sObject])) { $this->owl["validator"][$sTwin] = $this->owl["validator"][$sObject]; }
		foreach($this->owl["children"] as $sParent => $aChildren) {
			if(isset($aChildren[$sObject])) {
				$aChildren[$sTwin] = $sTwin;
				$this->owl["children"][$sParent] = $aChildren;
			}
		}
	
		return $this;
	}

	public function unjoin() {
		list($sJoined) = $this->getarguments("entity", func_get_args());

		$sObject = $this->attribute("object");
		$sJoined = $this->FormatName($sJoined);
		if($sObject==null || $sJoined==null) {
			self::errorMessage($this->object, 1001); // empty object name
			return false;
		} else if(!isset($this->owl["tables"][$sObject])) {
			self::errorMessage($this->object, 1004, $sObject); // object doesn't exists
			return false;
		} else if(!isset($this->owl["tables"][$sJoined])) {
			self::errorMessage($this->object, 1004, $sJoined); // object doesn't exists
			return false;
		}

		// padre-hijo
		foreach($this->owl["parents"] as $sParent => $sChild) {
			if($sChild==$sJoined && $sObject==$sParent) { unset($this->owl["parents"][$sParent]); }
		}
		unset($this->owl["children"][$sJoined][$sObject]);

		// join con tablas y views
		if(isset($this->owl["joins"][$sObject])) {
			foreach($this->owl["joins"][$sObject] as $sJoinWith => $sJoin) {
				$aJoin = explode(":", $sJoin);
				if($aJoin[1]==$sJoined) {
					unset($this->owl["joins"][$sObject][$sJoinWith]);
				}
			}
		}

		// views
		unset($this->owl["views"][$sJoined]["joins"][$sObject]);
		
		return $this;
	}

	private function ViewFields() {
		list($sObject) = $this->getarguments("entity", func_get_args());
		$sObject = $this->FormatName($sObject);
		$db = $this->argument("db");
		$sName = "_tmpviewfields_".self::call()->unique(8);
		$db->query("CREATE TEMPORARY TABLE `".$sName."` SELECT * FROM `".$sObject."` ORDER BY RAND() LIMIT 30");
		$aFields = $db->query("DESCRIBE `".$sName."`")->getall();
		$db->query("DROP TEMPORARY TABLE `".$sName."`");
		$aView = array();
		foreach($aFields as $aField) {
			$sType = substr($aField["Type"], 0, strpos($aField["Type"], ")"));
			$aType = explode("(", $sType);
			$aView[$aField["Field"]] = array(
				"name" => $aField["Field"],
				"label" => ucfirst(str_replace("_", " ", strtolower($aField["Field"]))),
				"type" => $aType[0],
				"length" => $aType[1]
			);
		}

		return $aView;
	}

	public function types() {
		$aTypes = $this->aTypes;
		asort($aTypes);
		return $aTypes;
	}

	public function fields() {
		$aFields = $this->aFields;
		asort($aFields);
		return $aFields;
	}

	public function createFromFile() {
		list($sFilePath, $sObject, $sTitle) = $this->getarguments("filepath,entity,title", func_get_args());
		$sType = strtolower(pathinfo($sFilePath, PATHINFO_EXTENSION));

		switch($sType) {
			case "xlsx":
			case "xls":
				$nIni = 2;
				$xls = self::call("excel")->load($sFilePath);
				$xls->calculate(true);
				$aSource["title"] = $xls->getTitle();
				$xls->unmergeAll(true);
				$aGetColumns = $xls->row(1);
				$aData = $xls->getall();
				break;

			case "txt":
			case "csv":
				$nIni = 1;
				$csv = self::call("file")->load($sFilePath);
				$sData = $csv->read();
				$aData = self::call("shift")->convert($sData, "csv-array", array("splitter"=>";"));
				$aGetColumns = $aData[0];
				break;
		}
	
		// columnas
		$aColumns = $aFields = array();
		foreach($aGetColumns as $sColumn) {
			$sFieldName = $this->FormatName($sColumn);
			if($sFieldName=="") { break; }
			$aFields[] = $sFieldName;
			$aColumns[$sFieldName] = array("label"=>$sColumn, "alias"=>"text", "default"=>"NULL", "null"=>true);
		}

		$this->aLoadData[$sObject] = array("data"=>$aData, "fields"=>$aFields);
		$this->aLoadDataIndex[$sObject] = $sFilePath;
		return $this->create($sObject, $sTitle, $aColumns);
	}

	public function normalize() {
		list($sField, $sNewObject, $sTitle) = $this->getarguments("field,newname,title", func_get_args());

		$sObject = $this->attribute("object");
		$sField = $this->FormatName($sField);
		$sNewObject = $this->FormatName($sNewObject);

		$aColumns = array(
			"codigo" => array("alias"=>"code", "label"=>"Código"),
			"nombre" => array("alias"=>"name", "label"=>"Nombre")
		);

		$sHash = self::call()->unique(8);
		$this->aNormalize[$sNewObject] = array($sObject, $sField, $sHash);

		$sLabel = $this->owl["tables"][$sObject][$sField];
		$this->select($sObject)
			->alter($sField, array("name"=>$sField."_".$sHash, "label"=>$sLabel."_source"))
			->add($sField, array("type"=>"@".$sNewObject, "label"=>$sLabel))
		;
		return $this->create($sNewObject, $sTitle, $aColumns);
	}

	public function presetfields() {
		return $this->aPresetFields;
	}

	public function view() {
		list($sObject, $sTitle) = $this->getarguments("entity,title", func_get_args());
		$sObject = $this->FormatName($sObject);
		if($sObject==null) {
			self::errorMessage($this->object, 1001); // empty object name
			return false;
		} else if(isset($this->owl["views"][$sObject])) {
			self::errorMessage($this->object, 1002, $sObject); // object alredy exists
			return false;
		}

		if($sTitle===null) { $sTitle = $sObject; }
		$aFields = $this->ViewFields($sObject);
		$this->owl["views"][$sObject] = array("title"=>$sTitle, "fields"=>$aFields);
		$this->owl["nest"]["objects"][$sObject]	= array("left"=>0, "top"=>0);
		return $this;
	}

	private function CreateStructure() {
		return <<<SQL
-- OWL CORE --------------------------------------------------------------------
-- log --
DROP TABLE IF EXISTS `__ngl_owl_log__`;
CREATE TABLE `__ngl_owl_log__` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`table` CHAR(128) NOT NULL,
	`row` INT UNSIGNED NOT NULL,
	`user` SMALLINT UNSIGNED DEFAULT NULL,
	`action` ENUM('insert','update','suspend','delete') NOT NULL DEFAULT 'insert',
	`date` DATETIME NOT NULL,
	`ip` CHAR(45) NOT NULL DEFAULT '',
	`changelog` MEDIUMTEXT NULL DEFAULT NULL,
	PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `table_idx` ON `__ngl_owl_log__` (`table`);
CREATE INDEX `row_idx` ON `__ngl_owl_log__` (`row`);
CREATE INDEX `user_idx` ON `__ngl_owl_log__` (`user`);

-- tables --
DROP TABLE IF EXISTS `__ngl_owl_structure__`;
CREATE TABLE `__ngl_owl_structure__` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` CHAR(128) NOT NULL,
	`columns` TEXT NOT NULL,
	`foreignkey` TEXT NULL,
	`relationship` TEXT NULL,
	`validate_insert` TEXT NULL,
	`validate_update` TEXT NULL,
	PRIMARY KEY (`id`) 
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `name_idx` ON `__ngl_owl_structure__` (`name`);

-- index --
DROP TABLE IF EXISTS `__ngl_owl_index__`;
CREATE TABLE `__ngl_owl_index__` (
	`id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
	`table` CHAR(128) NOT NULL DEFAULT '',
	`row` INT UNSIGNED NOT NULL,
	`imya` CHAR(32) NOT NULL DEFAULT '',
	`alvin` CHAR(32) NULL DEFAULT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX `table_idx` ON `__ngl_owl_index__` (`table`);
CREATE INDEX `row_idx` ON `__ngl_owl_index__` (`row`);
CREATE INDEX `imya_idx` ON `__ngl_owl_index__` (`imya`);

-- sentences --
DROP TABLE IF EXISTS `__ngl_sentences__`;
CREATE TABLE `__ngl_sentences__` (
	`name` VARCHAR(128) NOT NULL DEFAULT '',
	`type` ENUM('function','procedure','query','structure','trigger','view') NOT NULL,
	`sentence` MEDIUMTEXT NOT NULL,
	`dependencies` MEDIUMTEXT NOT NULL,
	`notes` VARCHAR(255) NOT NULL DEFAULT '',
	PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- PROJECT ENTITIES ------------------------------------------------------------\n
SQL;
	}

	private function CreateTableStructure($sTable, $aTableFields, $bCreate=false) {
		$aFields = $aAlter = $aIndex = $aIndexAlter = array();

		if($this->bUpdate==false || isset($this->aAdd[$sTable]) || isset($this->aAlterField[$sTable]) || $bCreate) {
			$aIndex[] = "CREATE UNIQUE INDEX `imya` ON `".$sTable."` (`imya`);";
			$aIndex[] = "CREATE INDEX `state` ON `".$sTable."` (`state`);";

			$sLastField = "state";
			foreach($aTableFields as $sField => $aField) {
				if($sField=="pid") {
					$aFields = array_reverse($aFields, true);
					$aFields["pid"] = "`pid` INT UNSIGNED NOT NULL";
					$aFields = array_reverse($aFields, true);
					$aIndex[] = "CREATE INDEX `pid` ON `".$sTable."` (`pid`);";
					continue;
				}

				$bAdd = isset($this->aAdd[$sTable]);
				$aFields[$sField] = $this->FieldDef($sField, $aField, $bAdd);
				if($aField["index"]!="--") {
					$sIndex = ($aField["index"]!="INDEX") ? $aField["index"]." " : "";
					$aIndex[] = $aIndexAlter[$sField] = "CREATE ".$sIndex."INDEX `".$sField."` ON `".$sTable."` (`".$sField."`);";
				}

				$sLastField = $sField;
			}
		}

		$sSQLStructure = "";
		if($this->bUpdate==false || isset($this->aAdd[$sTable]) || $bCreate) {
			$sSQLStructure .= "-- ".$sTable." --\n";
			$sSQLStructure .= "DROP TABLE IF EXISTS `".$sTable."`;\n";
			$sSQLStructure .= "CREATE TABLE `".$sTable."` (\n";
			$sSQLStructure .= "\t`id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT NOT NULL,\n";
			$sSQLStructure .= "\t`imya` CHAR(32) NOT NULL DEFAULT '',\n";
			$sSQLStructure .= "\t`state` ENUM('0', '1') NULL DEFAULT '1',\n";
			$sSQLStructure .= "\t".implode(",\n\t", $aFields);
			$sSQLStructure .= "\n) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n";
			if(count($aIndex)) { $sSQLStructure .= implode("\n", $aIndex)."\n"; }
			$sSQLStructure .= "\n";
		} else if(isset($this->aAlterField[$sTable])) {
			$aIndex = array();
			$sSQLStructure .= "-- ".$sTable." --\n";

			foreach($this->aAlterField[$sTable] as $sField => $aAfters) {
				foreach($aAfters as $sAfter) {
					if($sField=="pid" && $sAfter!=="@DROP") {
						$aIndex[] = "ALTER TABLE `".$sTable."` ADD INDEX (`pid`);";
					}
					if($sAfter==="@DROP") {
						$sSQLStructure .= "ALTER TABLE `".$sTable."` DROP COLUMN `".$sField."`;\n";
					} else if($sAfter==="@MODIFY" || $sAfter==="@CHANGE") {
						$sSQLStructure .= "ALTER TABLE `".$sTable."` ".substr($sAfter, 1)." COLUMN ".$aFields[$sField].";\n";
					} else {
						$sAfter = ($sAfter!==true) ? " AFTER `".$sAfter."`" : "";
						$sSQLStructure .= "ALTER TABLE `".$sTable."` ADD COLUMN ".$aFields[$sField].$sAfter.";\n";
					}
				}

				if(isset($aTableFields[$sField]["oldname"]) && $aTableFields[$sField]["oldindex"]!="--") {
					$aIndex[] = "DROP INDEX `".$aTableFields[$sField]["oldname"]."` ON `".$sTable."`;";
				}

				if(isset($aIndexAlter[$sField])) {
					$aIndex[] = $aIndexAlter[$sField];
				}
			}

			if(count($aIndex)) {
				rsort($aIndex);
				$sSQLStructure .= implode("\n", $aIndex)."\n";
			}
			$sSQLStructure .= "\n";
		}

		if(isset($aField)) { unset($aField["oldname"], $aField["oldindex"]); }

		return $sSQLStructure;
	}

	private function DefJoins($sObject, $sField, $sType=null) {
		if($sType!==null) {
			$aJoin = explode(":", substr($sType,1));
			if($sField!="pid") {
				if(!isset($this->owl["joins"][$sObject])) { $this->owl["joins"][$sObject] = array(); }
				$this->owl["joins"][$sObject][] = $sField.":".$aJoin[0];
			} else {
				if(!isset($this->owl["children"][$aJoin[0]])) { $this->owl["children"][$aJoin[0]] = array(); }
				if(isset($this->owl["parents"][$sObject])) {
					if(isset($this->owl["children"][$this->owl["parents"][$sObject]])) {
						unset($this->owl["children"][$this->owl["parents"][$sObject]][$sObject]);
					}
				}
				$this->owl["children"][$aJoin[0]][$sObject] = $sObject;
				$this->owl["parents"][$sObject] = $aJoin[0];
			}
		} else {
			if(isset($this->owl["joins"][$sObject])) {
				foreach($this->owl["joins"][$sObject] as $sIndex=>$sJoin) {
					if(strpos($sJoin, $sField.":")===0) {
						unset($this->owl["joins"][$sObject][$sIndex]);
					}
				}
				if(!count($this->owl["joins"][$sObject])) { unset($this->owl["joins"][$sObject]); }
			}
		}
	}

	private function DescribeColumns($sTable, $sAlias) {
		$aColumns = (isset($this->owl["tables"][$sTable])) ? $this->owl["tables"][$sTable] : $this->owl["views"][$sTable]["fields"];
		$aDescribe = array();
		foreach($aColumns as $sColumn => $sLabel) {
			$aDescribe[] = "`".$sAlias."`.`".$sColumn."` AS '".$sAlias."_".$sColumn."'";
		}
		return $aDescribe;
	}

	private function FormatName($sName) {
		$sName = trim($sName);
		$sName = strtolower($sName);
		$sName = str_replace(" ", "_", $sName);
		return preg_replace("/[^a-z-0-9\_]/is", "", $sName);
	}

	private function FieldDef($sField, $aField, $bAdd=false) {
		$sOldName = (isset($aField["oldname"]) && $aField["oldname"]!=$sField && !$bAdd) ? "`".$aField["oldname"]."` " : "";
		$sNameType = $sOldName."`".$sField."` ".$aField["type"];
		$nLength = (!empty($aField["length"])) ? "(".$aField["length"].")" : "";
		$sAttribs = ($aField["attrs"]!="--") ? $aField["attrs"] : "";
		$sNull = (!isset($aField["null"]) || $aField["null"]===false) ? "NOT NULL" : "NULL";
		$sDefault = "";
		if($aField["default"]!="NONE" && $aField["default"]!="'NONE'") {
			$sDefault = "DEFAULT ".$aField["default"];
		}

		return $sNameType." ".$nLength." ".$sAttribs." ".$sNull." ".$sDefault;
	}

	protected function SetObject($sObject) {
		if(isset($this->owl["tables"][$sObject])) {
			$this->attribute("object", $sObject);
			$this->attribute("objtype", "table");
		} else if(isset($this->owl["views"][$sObject])) {
			$this->attribute("object", $sObject);
			$this->attribute("objtype", "view");
		} else {
			self::errorMessage($this->object, 1004, $sObject); // object doesn't exists
			return false;
		}
		
		return $sObject;
	}

	private function Structure() {
		$aSentence = $this->owl;
		$aStructure = $aSentence["tables"];

		if(is_array($aSentence) && isset($aSentence["titles"])) {
			$aStructure = array();
			foreach($aSentence["titles"] as $sTable => $sTitle) {
				$aColumns = array();
				foreach($aSentence["tables"][$sTable] as $sName => $sLabel) {
					$aColumns[$sName] = array("name" => $sName, "label" => $sLabel);
				}

				if(isset($aSentence["joins"][$sTable])) {
					foreach($aSentence["joins"][$sTable] as $sJoin) {
						$aJoin = explode(":", $sJoin);
						if(isset($aColumns[$aJoin[0]])) {
							// $aColumns[$aJoin[0]]["join"] = array("table"=>$aJoin[1], "alias"=>$aJoin[2]);
							$aColumns[$aJoin[0]]["join"] = $aJoin[1];
						}
					}
				}

				if(isset($aSentence["children"][$sTable])) {
					$aColumns["id"]["children"] = array();
					foreach($aSentence["children"][$sTable] as $sChild) {
						$aColumns["id"]["children"][] = $sChild;
					}
				}

				$sParent = "";
				if(isset($aColumns["pid"]) && isset($aSentence["parents"][$sTable])) {
					$sParent = $aSentence["parents"][$sTable];
					$aColumns["pid"]["join"] = array("table"=>$aSentence["parents"][$sTable], "alias"=>$aSentence["parents"][$sTable]);
				}

				$aStructure[$sTable] = array(
					"title"		=> $sTitle, 
					"name"		=> $sTable, 
					"parent"	=> $sParent, 
					"columns"	=> $aColumns
				);
			}

			// joins inversos
			foreach($aSentence["joins"] as $sTable => $aJoins) {
				foreach($aJoins as $sJoin) {
					$aJoin = explode(":", $sJoin);
					if(isset($aStructure[$aJoin[1]])) {
						if(!isset($aStructure[$aJoin[1]]["columns"]["id"]["rjoin"])) {
							$aStructure[$aJoin[1]]["columns"]["id"]["rjoin"] = array();
						}
						
						$aStructure[$aJoin[1]]["columns"]["id"]["rjoin"][$sTable] = array("table"=>$sTable, "using"=>$aJoin[0]);
					}
				}
			}
		}

		ksort($aStructure, SORT_NATURAL);
		return $aStructure;
	}
}

?>
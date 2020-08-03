<?php
/*
# Nogal
*the most simple PHP Framework* by hytcom.net
GitHub @hytcom
___
  
# zip
## nglZip *extends* nglBranch *implements* inglBranch [2018-11-29]
Gestiona archivos .ZIP

https://github.com/hytcom/wiki/blob/master/nogal/docs/zip.md

*/
namespace nogal;

class nglZip extends nglBranch implements inglBranch {

	private $zip;
	private $aErrors;

	final protected function __declareArguments__() {
		$vArguments							= array();
		$vArguments["zipfile"]				= array('$mValue', "document.zip");
		$vArguments["mode"]					= array('$mValue', "create"); // create | overwrite | open
		$vArguments["extract"]				= array('$mValue', null);
		$vArguments["extract_to"]			= array('$mValue', null);
		$vArguments["content"]				= array('$mValue', null);
		$vArguments["filepath"]				= array('$mValue', "");
		$vArguments["downname"]				= array('$mValue', "document.zip");
		return $vArguments;
	}

	final protected function __declareAttributes__() {
		$vAttributes = array();
		return $vAttributes;
	}

	final protected function __declareVariables__() {
	}

	final public function __init__() {
		$aErrors[\ZipArchive::ER_EXISTS]	= "File already exists (".\ZipArchive::ER_EXISTS.")";
		$aErrors[\ZipArchive::ER_INCONS]	= "Zip archive inconsistent (".\ZipArchive::ER_INCONS.")";
		$aErrors[\ZipArchive::ER_MEMORY]	= "Malloc failure (".\ZipArchive::ER_MEMORY.")";
		$aErrors[\ZipArchive::ER_NOENT]		= "No such file (".\ZipArchive::ER_NOENT.")";
		$aErrors[\ZipArchive::ER_NOZIP]		= "Not a zip archive (".\ZipArchive::ER_NOZIP.")";
		$aErrors[\ZipArchive::ER_OPEN]		= "Can't open file (".\ZipArchive::ER_OPEN.")";
		$aErrors[\ZipArchive::ER_READ]		= "Read error (".\ZipArchive::ER_READ.")";
		$aErrors[\ZipArchive::ER_SEEK]		= "Seek error (".\ZipArchive::ER_SEEK.")";
		$this->aErrors = $aErrors;
		$this->zip = null;
	}

	public function load() {
		list($sZipFile,$sMode) = $this->getarguments("zipfile,mode", func_get_args());
		$sMode = strtolower($sMode);
		$nMode = 0;
		if($sMode=="create") {
			$nMode = \ZipArchive::CREATE;
		} else if($sMode=="overwrite") {
			$nMode = \ZipArchive::OVERWRITE;
		}

		if($sZipFile===true) {
			$sZipFile = sys_get_temp_dir().NGL_DIR_SLASH.self::call()->unique().".zip";
		} else {
			$sZipFile = self::call()->sandboxPath($sZipFile);
		}

		$this->zip = new \ZipArchive;
		$nFlag = (!file_exists($sZipFile) && $nMode) ? $nMode : 0;
		$nError = $this->zip->open($sZipFile, $nFlag);
		if($nError!==true) {
			self::errorMessage($this->object, 1002, $this->aErrors[$nError]);
			return false;
		}

		return $this;
	}

	public function create() {
		if($this->zip===null) { self::errorMessage($this->object, 1001); return false; }
		list($sFilePath,$sContent) = $this->getarguments("filepath,content", func_get_args());
		
		if($sContent!==null) {
			$this->zip->addFromString($sFilePath, $sContent);
		} else {
			$this->zip->addEmptyDir($sFilePath);
		}
	}

	public function add() {
		if($this->zip===null) { self::errorMessage($this->object, 1001); return false; }
		list($sFilePath) = $this->getarguments("filepath", func_get_args());
		
		$sSandBoxPath = self::call()->sandboxPath($sFilePath);
		if(file_exists($sSandBoxPath)) { $this->zip->addFile($sSandBoxPath, $sFilePath); return $this; }
		self::errorMessage($this->object, 1003, $sSandBoxPath);
	}

	public function addDir() {
		if($this->zip===null) { self::errorMessage($this->object, 1001); return false; }
		list($sFilePath) = $this->getarguments("filepath", func_get_args());
		
		$sSandBoxPath = self::call()->sandboxPath($sFilePath);
		$ls = self::call("files")->ls($sFilePath, "*", "signed", true);
		foreach($ls as $sFile) {
			$sLocalFile = str_replace(array("*", $sSandBoxPath), "", $sFile);
			if($sLocalFile[0]==NGL_DIR_SLASH) { $sLocalFile = substr($sLocalFile, 1); }
			if($sFile[0]=="*") {
				$this->zip->addEmptyDir($sLocalFile);
			} else {
				$this->zip->addFile($sFile, $sLocalFile);
			}
		}
		
		return $this;
	}

	public function getall() {
		if($this->zip===null) { self::errorMessage($this->object, 1001); return false; }
		$aFiles = array();
		for($x = 0; $x < $this->zip->numFiles; $x++) {
			$sFile = $this->zip->getNameIndex($x);
			if($sFile!==false) { $aFiles[$x] = $sFile; }
		}
		sort($aFiles);
		return $aFiles;
	}

	public function unzip() {
		if($this->zip===null) { self::errorMessage($this->object, 1001); return false; }
		list($sExtractTo, $mExtract) = $this->getarguments("extract_to,extract", func_get_args());
		$sExtractTo = self::call()->sandboxPath($sExtractTo);
		return $this->zip->extractTo($sExtractTo, $mExtract);
	}

	public function download() {
		if($this->zip===null) { self::errorMessage($this->object, 1001); return false; }
		if(count(self::errorGetLast())) { exit(); }

		list($sFileName) = $this->getarguments("downname", func_get_args());
		$sFilePath = $this->zip->filename;
		$this->zip->close();

		header("Content-Description: File Transfer");
		header("Content-Type: application/zip");
		header("Content-Transfer-Encoding: binary");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Pragma: public");
		header("Content-Disposition: attachment; filename=\"".$sFileName."\"");
		header("Content-Length: ".filesize($sFilePath));
		die(readfile($sFilePath));
	}
}

?>
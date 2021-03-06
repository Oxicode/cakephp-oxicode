<?php
/**
 * CakePHP Database Backup
 *
 * Backups structure and data from cake's database.
 * Usage:
 * $ cake Backups.backup
 * To backup all tables structure and data from default
 *
 * TODO
 * Settings to choose datasource, table and output directory
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright 2012, Maldicore Group Pvt Ltd
 * @link      https://github.com/Maldicore/Backups
 * @package   plugns.Backups
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('CakeSchema', 'Model');
App::uses('ConnectionManager', 'Model');
App::uses('Inflector', 'Utility');
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
App::uses('CakeNumber', 'Utility');
// App::uses('Sanitize', 'Utility');

class BackupShell extends Shell {

/**
 * Contains arguments parsed from the command line.
 *
 * @var array
 * @access public
 */
	public $args;

	public $separador = "/*======*/";

	public $path = 'Backups/';

	public $dataSourceName = 'default';

	public $excluidos = array('Aro', 'Aco', 'ArosAco', 'CakeSession');

/**
 * Override main() for help message hook
 *
 * @access public
 */
	public function main() {
		$path = $this->path;

		$Folder = new Folder($path, true);

		$fileSufix = date('Ymd\_His') . '.sql';
		$file = $path . $fileSufix;
		if (!is_writable($path)) {
			trigger_error('The path "' . $path . '" isn\'t writable!', E_USER_ERROR);
		}

		$this->out("Backing up...\n");
		$File = new File($file);

		$db = ConnectionManager::getDataSource($this->dataSourceName);

		$config = $db->config;
		$this->connection = "default";

		foreach ($db->listSources() as $table) {

			$table = str_replace($config['prefix'], '', $table);
			// $table = str_replace($config['prefix'], '', 'dinings');
			$ModelName = Inflector::classify($table);

			if (in_array($ModelName, $this->excluidos)) {
				continue;
			}

			$Model = ClassRegistry::init($ModelName);
			$Model->virtualFields = array();
			$DataSource = $Model->getDataSource();

			$this->Schema = new CakeSchema(array('connection' => $this->connection));

			$cakeSchema = $db->describe($table);
			// $CakeSchema = new CakeSchema();
			$this->Schema->tables = array($table => $cakeSchema);

			$File->write("\n/* Drop statement for {$table} */\n");
			$File->write("\nSET foreign_key_checks = 0;\n\n");
			#$File->write($DataSource->dropSchema($this->Schema, $table) . "\n");
			$File->write($DataSource->dropSchema($this->Schema, $table));
			$File->write("SET foreign_key_checks = 1;\n");

			$File->write("\n/* Backuping table schema {$table} */\n");

			$File->write($DataSource->createSchema($this->Schema, $table) . "\n");

			$File->write("/* Backuping table data {$table} */\n");

			unset($valueInsert, $fieldInsert);
			$rows = $Model->find('all', array('recursive' => -1));
			$quantity = 0;

			$File->write($this->separador . "\n");
			if (count($rows) > 0) {
				$fields = array_keys($rows[0][$ModelName]);
				$values = array_values($rows);
				$count = count($fields);

				for ($i = 0; $i < $count; $i++) {
					$fieldInsert[] = $DataSource->name($fields[$i]);
				}
				$fieldsInsertComma = implode(', ', $fieldInsert);

				foreach ($rows as $k => $row) {
					unset($valueInsert);
					for ($i = 0; $i < $count; $i++) {
						$valueInsert[] = $DataSource->value(utf8_encode($row[$ModelName][$fields[$i]]), $Model->getColumnType($fields[$i]), false);
					}

					$query = array(
						'table' => $DataSource->fullTableName($table),
						'fields' => $fieldsInsertComma,
						'values' => implode(', ', $valueInsert)
					);
					$File->write($DataSource->renderStatement('create', $query) . ";\n");
					$quantity++;
				}

			}

			$this->out('Model "' . $ModelName . '" (' . $quantity . ')');
		}
		$this->out("Backup: " . $file);
		$this->out("Peso:: " . CakeNumber::toReadableSize(filesize($file)));
		$File->close();

		if (class_exists('ZipArchive') && filesize($file) > 100) {
			$zipear = $this->in('Deseas zipear este backup', null, 's');
			$zipear = strtolower($zipear);

			if($zipear === 's'):
				$this->hr();
				$this->out('Zipping...');
				$zip = new ZipArchive();
				$zip->open($file . '.zip', ZIPARCHIVE::CREATE);
				$zip->addFile($file, $fileSufix);
				$zip->close();

				$this->out("Peso comprimido: " . CakeNumber::toReadableSize(filesize($file . '.zip')));
				$this->out("Listo!");

				if (file_exists($file . '.zip') && filesize($file) > 10) {
					unlink($file);
				}
				$this->out("Database Backup Successful.\n");

			endif;
		}
	}

	public function restore() {
		$path = $this->path;

		$tmpath = APP_DIR . DS . 'tmp';

		$backupFolder = new Folder($path);

		// Get the list of files
		list($dirs, $files) = $backupFolder->read();

		// Remove any un related files
		foreach ($files as $i => $file) {
			if (!preg_match('/\.sql/', $file)) {
				unset($files[$i]);
			}
		}

		// Sort, explode the files to an array and list files
		sort($files, SORT_NUMERIC);
		foreach ($files as $i => $file) {
			$fileParts = explode(".", $file);
			$backupDate = strtotime(str_replace("_", "", $fileParts[0]));
			$this->out("[" . $i . "]: " . date("F j, Y, g:i:s a", $backupDate));
		}

		App::import('Model', 'AppModel');

		$model = new AppModel(false, false);

		// Prompt for the file to restore to
		$this->hr();
		$uResponse = $this->in('Type Backup File Number? [or press enter to skip]');

		if ($uResponse == "") {
			$this->out('Exiting');
		} else {
			$zipfile = $path . $files[$uResponse];
			if (array_key_exists($uResponse, $files)) {
				$this->out('Restoring file: ' . $zipfile);
				$fileParts = explode(".", $files[$uResponse]);

				if (isset($fileParts[2]) && $fileParts[2] == 'zip') {
					$this->out('Unzipping File');
					if (class_exists('ZipArchive')) {
						$zip = new ZipArchive;
						if ($zip->open($zipfile) === true) {
							$zip->extractTo($tmpath);
							$unzippedFile = $tmpath . DS . $zip->getNameIndex(0);
							$zip->close();
							$this->out('Successfully Unzipped');
						} else {
							$this->out('Unzip Failed');
							$this->_stop();
						}
					} else {
						$this->out('ZipArchive not found, cannot Unzip File!');
						$this->_stop();
					}
				}

				if (($sqlContent = file_get_contents($filename = $unzippedFile)) !== false) {
					$this->out('Restoring Database');
					$sql = explode($this->separador, $sqlContent);
					foreach ($sql as $key => $s) {
						debug($s);

						if (trim($s)) {
							$result = $model->query($s);
						}
					}
					unlink($unzippedFile);
				} else {
					$this->out("Couldn't load contents of file {$unzippedFile}, aborting...");
					unlink($unzippedFile);
					$this->_stop();
				}
			} else {
				$this->out("Invalid File Number");
				$this->_stop();
			}
		}
	}
}

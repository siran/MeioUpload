<?php
/**
 * MeioUpload Behavior Tests
 *
 * @package      meio_upload
 * @subpackage   meio_upload.tests.cases.models.behaviors
 * @since        3.0
 * @link         http://github.com/jrbasso/MeioUpload
 * @license      MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::import('Behavior', 'MeioUpload.MeioUpload');

/**
 * Meiomodel
 *
 */
class Meiomodel extends CakeTestModel {
/**
 * Name
 *
 * @var string
 * @access public
 */
	var $name = 'Meiomodel';

/**
 * useTable
 *
 * @var boolean
 * @access public
 */
	var $useTable = false;

/**
 * Behaviors
 *
 * @var array
 * @access public
 */
	var $actsAs = array(
		'MeioUpload.MeioUpload' => array(
			'filename' => array()
		)
	);
}

/**
 * MeioUploadTestCase
 *
 */
class MeioUploadTestCase extends CakeTestCase {

/**
 * MeioUpload
 *
 * @var object
 * @access public
 */
	var $MeioUpload = null;

/**
 * TestModel
 *
 * @var object
 * @access public
 */
	var $TestModel = null;

/**
 * start
 *
 * @return void
 * @access public
 */
	function start() {
		parent::start();
		$this->TestModel = new Meiomodel();
		$this->MeioUpload =& $this->TestModel->Behaviors->MeioUpload;
	}

/**
 * end
 *
 * @return void
 * @access public
 */
	function end() {
		$folder =& new Folder(WWW_ROOT . 'uploads' . DS . 'meiomodel');
		$folder->delete();
		parent::end();
	}

/**
 * testReplaceTokens
 *
 * @return void
 * @access public
 */
	function testReplaceTokens() {
		$result = $this->MeioUpload->_replaceTokens($this->TestModel, 'test', 'field');
		$this->assertEqual($result, 'test');

		$result = $this->MeioUpload->_replaceTokens($this->TestModel, '{Model}aaa', 'field');
		$this->assertEqual($result, '{Model}aaa');

		$result = $this->MeioUpload->_replaceTokens($this->TestModel, '{ModelName}aaa{fieldName}xxx{DS}iii\\o//', 'field');
		$this->assertEqual($result, 'meiomodelaaafieldxxx' . DS . 'iii' . DS . 'o' . DS . DS);
	}

/**
 * testFixName
 *
 * @return void
 * @access public
 */
	function testFixName() {
		$this->TestModel->data = array(
			$this->TestModel->alias => array(
				'filename' => array(
					'name' => 'xxx.jpg'
				),
				'otherfield' => 'nice'
			)
		);

		$this->MeioUpload->_fixName($this->TestModel, 'filename', false);
		$this->assertEqual($this->TestModel->data[$this->TestModel->alias]['filename']['name'], 'xxx.jpg');

		$this->TestModel->data[$this->TestModel->alias]['filename']['name'] = 'default.jpg';
		$this->MeioUpload->_fixName($this->TestModel, 'filename', false);
		$this->assertEqual($this->TestModel->data[$this->TestModel->alias]['filename']['name'], 'default.jpg');

		$this->TestModel->data[$this->TestModel->alias]['filename']['name'] = 'default_1.hello.jpg';
		$this->MeioUpload->_fixName($this->TestModel, 'filename', false);
		$this->assertEqual($this->TestModel->data[$this->TestModel->alias]['filename']['name'], 'default_1_hello.jpg');

		$file = WWW_ROOT . 'uploads' . DS . 'meiomodel' . DS . 'filename' . DS . 'default.jpg';
		if ($this->skipIf(!@touch($file), 'Fail to create file.')) {
			return;
		}
		$this->TestModel->data[$this->TestModel->alias]['filename']['name'] = 'default.jpg';
		$this->MeioUpload->_fixName($this->TestModel, 'filename', true);
		$this->assertEqual($this->TestModel->data[$this->TestModel->alias]['filename']['name'], 'default-0.jpg');
		unlink($file);
	}

/**
 * testSplitFilenameAndExt
 *
 * @return void
 * @access public
 */
	function testSplitFilenameAndExt() {
		$result = $this->MeioUpload->_splitFilenameAndExt('default.jpg');
		$this->assertEqual($result, array('default', 'jpg'));

		$result = $this->MeioUpload->_splitFilenameAndExt('default-1.jpg');
		$this->assertEqual($result, array('default-1', 'jpg'));

		$result = $this->MeioUpload->_splitFilenameAndExt('default_1.jpg');
		$this->assertEqual($result, array('default_1', 'jpg'));

		$result = $this->MeioUpload->_splitFilenameAndExt('default.xxx');
		$this->assertEqual($result, array('default', 'xxx'));

		$result = $this->MeioUpload->_splitFilenameAndExt('default.hi.jpg');
		$this->assertEqual($result, array('default.hi', 'jpg'));
	}

/**
 * testSizeToBytes
 *
 * @return void
 * @access public
 */
	function testSizeToBytes() {
		$result = $this->MeioUpload->_sizeToBytes(100);
		$this->assertEqual($result, 100);

		$result = $this->MeioUpload->_sizeToBytes(2000);
		$this->assertEqual($result, 2000);

		$result = $this->MeioUpload->_sizeToBytes('1KB');
		$this->assertEqual($result, 1024);

		$result = $this->MeioUpload->_sizeToBytes('1 KB');
		$this->assertEqual($result, 1024);

		$result = $this->MeioUpload->_sizeToBytes('1 kb');
		$this->assertEqual($result, 1024);

		$this->expectError();
		$result = $this->MeioUpload->_sizeToBytes('1 xx');
		$this->assertEqual($result, 2097152);
	}

/**
 * testCreateFolders
 *
 * @return void
 * @access public
 */
	function testCreateFolders() {
		if ($this->skipIf(is_dir(WWW_ROOT . 'test'), 'Directory "test" in webroot exists.')) {
			return;
		}
		$this->MeioUpload->_createFolders('test', array('a', 'b', 'c'));
		$this->assertTrue(is_dir(WWW_ROOT . 'test'));
		$this->assertTrue(is_dir(WWW_ROOT . 'test' . DS . 'thumb' . DS . 'a'));
		$this->assertTrue(is_dir(WWW_ROOT . 'test' . DS . 'thumb' . DS . 'b'));
		$this->assertTrue(is_dir(WWW_ROOT . 'test' . DS . 'thumb' . DS . 'c'));
		$this->assertFalse(is_dir(WWW_ROOT . 'test' . DS . 'thumb' . DS . 'd'));
		$folder = new Folder();
		$folder->cd(WWW_ROOT . 'test');
		$folder->delete();
	}
}

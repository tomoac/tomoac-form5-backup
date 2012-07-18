<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));

class DashboardFormTomoacBackupRestoreController extends Controller {

	/* ---------------- common code (2012/7/18) ---------------- */

	public function restore_form() {

		$errmes  = '';
		$db = Loader::db();
		$js = Loader::helper('json');

		$u = new User();
		if(!$u->isSuperUser())
			return;

		$bid = $_POST['bid'];

		// msgIDのベース値設定
		$maxmsqID = 1 + $db->GetOne("SELECT max(msqID) FROM btFormTomoacQuestions");

		// questionSetIdのピックアップ
		$questionSetId = $_POST['questionSetId'];

		$msqIDar = array();
		$asIDar = array();

		// === アップロードファイル (btFormTomoacQuestions) === //
		$fn = $_FILES['json']['tmp_name'];
		list( $errmes, $jsonar ) = $this->get_json_array( $fn, 0 );
		if($errmes != '') {
			$this->set('message', $errmes);
			return;
		}
		foreach($jsonar as $json) {
			if($json == '')
				continue;
			$jss = $js->decode($json);
			$valar = array();
			$sql = '';
			$i = 0;
			foreach($jss as $key=>$val) {
				if($i == 0)
					$sql.= 'INSERT INTO btFormTomoacQuestions (';
				else if($i < 19)	//
					$sql.= ', ';
				$sql.= $key;
				if($key == 'qID')
					$valar[] = '';
				else if($key == 'msqID') {
					$msqIDar[$val] = $maxmsqID + $val;
					$valar[] = $msqIDar[$val];
				} else if($key == 'bID')
					$valar[] = $_POST['bid'];
				else if($key == 'questionSetId')
					$valar[] = $_POST['questionSetId'];
				else
					$valar[] = $val;
				$i++;
			}
			$sql.= ') VALUE (';
			if($sql != '') {
				$i = 0;
				foreach($valar as $val) {
					if($i != 0) $sql.= ', ';
					$sql.= "'" . array_shift($valar) . "'";
					$i++;
				}
				$sql.= ')';
			}
			//error_log($sql);
			$db->query($sql);
		}
		if($_POST['data'] != 'data') {	// only Form
			if($errmes == '')
				$this->set('message', '"'.$_FILES['json']['name'].'" '.t('was restored.'));
			else
				$this->set('message', $errmes);
			return;
		}

		// === アップロードファイル (AnswerSet) === //
		list( $errmes, $jsonar ) = $this->get_json_array( $fn, 1 );
		if($errmes != '') {
			$this->set('message', $errmes);
			return;
		}
		foreach($jsonar as $json) {
			if($json == '')
				continue;
			$jss = $js->decode($json);
			$valar = array();
			$sql = "INSERT INTO btFormTomoacAnswerSet (";
			foreach($jss as $key=>$val) {
				if($key == 'questionSetId') {
					$sql .= $key;
					$valar[] = $questionSetId;		// 取り込むテーブルのIDを埋め込む
				} else if(($key == 'created')||($key == 'uID')) {
					$sql .= ','.$key;
					$valar[] = $val;
				}
			}
			$sql .= ") VALUE (";
			$sql .= "'".array_shift($valar)."',";
			$sql .= "'".array_shift($valar)."',";
			$sql .= "'".array_shift($valar)."')";
			//error_log($sql);
			$db->query($sql);

			$asIDar[] = $db->GetOne("SELECT LAST_INSERT_ID()");
		}

		// === アップロードファイル (btFormTomoacAnswers) === //
		list( $errmes, $jsonar ) = $this->get_json_array( $fn, 2 );
		if($errmes != '') {
			$this->set('message', $errmes);
			return;
		}
		foreach($jsonar as $json) {
			if($json == '')
				continue;
			$jss = $js->decode($json);
			$valar = array();
			$sql = "INSERT INTO btFormTomoacAnswers (";
			foreach($jss as $key=>$val) {
				if($key == 'asID') {
					$sql .= $key;
					$valar[] = $asIDar[$val];		// AnswerSetで挿入時の値を参照
				} else if($key == 'msqID') {
					$sql .= ','.$key;
					$valar[] = $msqIDar[$val];		// QuestionsのmsqIDの値を参照
				} else if(($key == 'answer')||($key == 'answerLong')) {
					$sql .= ','.$key;
					$valar[] = $val;
				}
			}
			$sql .= ") VALUE (";
			$sql .= "'".array_shift($valar)."',";
			$sql .= "'".array_shift($valar)."',";
			$sql .= "'".array_shift($valar)."',";
			$sql .= "'".array_shift($valar)."')";
			//error_log($sql);
			$db->query($sql);
		}
		if($errmes == '')
			$this->set('message', '"'.$_FILES['json']['name'].'" '.t('was restored.'));
		else
			$this->set('message', $errmes);
	}

	/* ===========[ get json data ]=========== */

	function get_json_array( $fn, $table ) {

		$js = Loader::helper('json');

		if(($fp = fopen($fn, "r")) === FALSE)
			return array( t('Restore file could not open.'), NULL);

		$jsontx = '';
		if(filesize($fn) == 0)
			return array( t('Restore file has zero lines.'), NULL);
		else if(($jsontx.= fread($fp, filesize($fn))) === FALSE)
			return array( t('Restore file could not read.'), NULL);
		if(fclose($fp) === FALSE)
			return array( t('Restore file could not close.'), NULL);

		$jsonar = explode("\n",$jsontx);
		if(count($jsonar) == 0)
			return array( t('Restore file has zero lines.'), NULL);

		$jsonout = array();
		foreach($jsonar as $json) {
			if($json == '')
				continue;

			switch($table) {
			case '0':	// btFormTomoacQuestions
				$jss = $js->decode($json);
				foreach($jss as $key=>$val)
					if($key == 'qID') {
						$jsonout[] = $json;
						break;
					}
				break;
			case '1':	// btFormTomoacAnswerSet
				$jss = $js->decode($json);
				foreach($jss as $key=>$val)
					if($key == 'created') {
						$jsonout[] = $json;
						break;
					}
				break;
			case '2':	// btFormTomoacAnswers
				$jss = $js->decode($json);
				foreach($jss as $key=>$val)
					if($key == 'aID') {
						$jsonout[] = $json;
						break;
					}
				break;
			}
		}
		return array( NULL, $jsonout );
	}
}

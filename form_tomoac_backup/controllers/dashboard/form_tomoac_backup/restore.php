<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));

class DashboardFormTomoacBackupRestoreController extends Controller {

	public function restore_form() {
//		error_log("restore",0);
		$errmes  = '';
		$db = Loader::db();
		$js = Loader::helper('json');

		$u = new User();
		if(!$u->isSuperUser())
			return;

		if($_POST['upload2'] != '') {	// Back Data of FORM

			$bid = $_POST['bid'];

			// msqIDの値設定（btFromTomoacQuestionsから）
			$msqIDar = array();
			$sql = "SELECT msqID FROM btFormTomoacQuestions WHERE bID=$bid";
			$rows = $db->query($sql);
			foreach($rows as $row)
				foreach($row as $key=>$val)
					$msqIDar[] = $val;

			// questionSetIdのピックアップ
			$questionSetId = $_POST['questionSetId'];

			$asIDar = array();

			$fn1 = $_FILES['json']['tmp_name'][1];
			$fn2 = $_FILES['json']['tmp_name'][2];

			if(($fn1 != '')&&($fn2 != '')) {

				// === アップロードファイル (AnswerSet) === //

				list( $errmes, $jsonar ) = $this->get_json_array( $fn1 );
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
//					error_log($sql);
					$db->query($sql);

					$asIDar[] = $db->GetOne("SELECT LAST_INSERT_ID()");
				}
				// === アップロードファイル (btFormTomoacAnswers) === //

				list( $errmes, $jsonar ) = $this->get_json_array( $fn2 );
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
//					error_log($sql);
					$db->query($sql);
				}
			}
/*
			// === アップロードファイル (btFormTomoacQuestions) === //
			$fn0 = $_FILES['json']['tmp_name'][0];
			list( $errmes, $jsonar ) = $this->get_json_array( $fn0 );
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
					else if($key == 'msqID')
						$valar[] = ++$maxid;
					else if($key == 'bID')
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
//				error_log($sql);
				$db->query($sql);
			}
*/
			if($errmes == '')
				$this->set('message', '"'.$_FILES['json']['name'].'" '.t('was restored.'));
			else
				$this->set('message', $errmes);
		}
		else if($_POST['upload1'] != '') {	// Back FORM Question

			// アップロードファイル
			$fn = $_FILES['json']['tmp_name'];
			if(($fp = fopen($fn, "r")) === FALSE) {
				$this->set('message', t('Restore file could not open.'));
				return;
			}
			$jsontx = '';
			if(filesize($fn) == 0)
				$errmes = t('Restore file has zero lines.');
			else if(($jsontx.= fread($fp, filesize($fn))) === FALSE)
				$errmes = t('Restore file could not read.');
			if(fclose($fp) === FALSE)
				$errmes = t('Restore file could not close.');
			if($errmes != '') {
				$this->set('message', $errmes);
				return;
			}
			$jsonar = explode("\n",$jsontx);
			if(count($jsonar) == 0) {
				$this->set('message', t('Restore file has zero lines.'));
				return;
			}

			// msgIDのベース値設定
			$maxid = $db->GetOne("SELECT max(msqID) FROM btFormTomoacQuestions");

			// SQL文の組立て
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
					else if($key == 'msqID')
						$valar[] = ++$maxid;
					else if($key == 'bID')
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
//				error_log($sql);
				$db->query($sql);
			}
			if($errmes == '')
				$this->set('message', '"'.$_FILES['json']['name'].'" '.t('was restored.'));
			else
				$this->set('message', $errmes);
		}
	}

	/* ===========[ get json data ]=========== */

	function get_json_array( $fn ) {

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

		return array( NULL, $jsonar );
	}
}

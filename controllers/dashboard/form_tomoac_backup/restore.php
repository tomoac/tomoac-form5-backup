<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));

class DashboardFormTomoacBackupRestoreController extends Controller {

	public function restore_form() {
//		error_log("restore",0);
		$errmes  = '';
		$db = Loader::db();
		$js = Loader::helper('json');

		$u = new User();
		if ($u->isSuperUser()) {

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
}

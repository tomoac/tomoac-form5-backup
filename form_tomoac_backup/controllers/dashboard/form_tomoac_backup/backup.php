<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));

class DashboardFormTomoacBackupBackupController extends Controller {

	public function backup_form() {
//		error_log("backup",0);
		$errmes = '';

		$db = Loader::db();
		$js = Loader::helper('json');
		$fh = Loader::helper('file');

		$path = "./files/tomoacform5";
		if(!is_dir($path))
			if(!mkdir( $path )) {
				$this->set('message', t('Backup directory could not make.'));
				return;
			}
		$u = new User();
		if ($u->isSuperUser()) {

			if($_POST['function'] == 'backup') {	// backup
				$fn = date('Y-m-d_H:i').'_'.$_POST['surveyName'].'('.$_POST['bID'].').json';
				$fh->clear($path.'/'.$fn);

				$bid = $_POST['bID'];
				$rows = $db->query("SELECT * FROM btFormTomoacQuestions WHERE bID='".$bid."' ORDER BY position");
				foreach($rows as $row) {
					$fh->append($path.'/'.$fn, $js->encode($row) ."\n");
				}
				if($errmes == '')
					$this->set('message', '"'.$fn.'" '.t('was backuped.'));
				else
					$this->set('message', $errmes);
			}
			if($_POST['function'] == 'download') {	// download
				$fn = $path .'/'. $_POST['filename'];
				$fh->forceDownload($fn);
			}
			if($_POST['function'] == 'delete') {	// delete
				$fn = $path .'/'. $_POST['filename'];
				if(unlink($fn))
					$this->set('message', '"'.$fn.'" '.t('was deleted.'));
				else
					$this->set('message', t('could not delete.'));
			}
		}
	}
}

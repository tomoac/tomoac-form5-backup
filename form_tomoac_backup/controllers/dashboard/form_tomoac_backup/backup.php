<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));

class DashboardFormTomoacBackupBackupController extends Controller {

	/* ---------------- common code (2012/7/18) ---------------- */

	public function backup_form() {

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

			switch( $_POST['function'] ) {
			
			case 'backup':		// backup

				$surveyname = $_POST['surveyName'];
				$bid = $_POST['bID'];
				$filename = date('Y-m-d_H:i')."_$surveyname($bid)";

				// -------- dump from btFormTomoacQuestions -------- //
				$fn = $filename.".json";
				$fh->clear($path.'/'.$fn);

				$rows = $db->query("SELECT * FROM btFormTomoacQuestions WHERE bID=$bid ORDER BY position,msqID");
				$msqid = 0;
				foreach($rows as $row) {
					foreach($row as $key=>$val) {
						if($key == 'qID')
							$row{'qID'} = '';
						if($key == 'msqID')
							$row{'msqID'} = $msqid++;
					}
					$fh->append($path.'/'.$fn, $js->encode($row) ."\n");
				}
				if($_POST['data'] != 'data') {	// only Form
					if($errmes == '')
						$this->set('message', '"'.$fn.'" '.t('was backuped.'));
					else
						$this->set('message', $errmes);
					break;
				}

				// -------- dump from btFormTomoacAnswers -------- //
				$msqIDlist = '';
				$sql = "SELECT msqID FROM btFormTomoacQuestions WHERE bID=$bid ORDER BY position,msqID";
				//error_log($sql,0);
				$rows = $db->query($sql, $val);
				foreach($rows as $row) {
					foreach($row as $key=>$val) {
						if($msqIDlist != '')
							$msqIDlist .= ' OR ';
						$msqIDlist .= 'msqID='.$val;	// listup msqID
					}
				}
				$sql = "SELECT * FROM btFormTomoacAnswers WHERE $msqIDlist ORDER BY asID,msqID";
				//error_log($sql,0);
				$rows = $db->query($sql);
				$newasID = 0;
				$curasID = 0;
				foreach($rows as $row) {
					$row{'aID'} = '';
					if($curasID != $row{'asID'}) {
						if($curasID != 0)
							$newasID++;
						$curasID = $row{'asID'};
						$curmsqID = 0;
						$row{'asID'} = $newasID;
						$row{'msqID'} = $curmsqID++;
					} else {
						$row{'asID'} = $newasID;
						$row{'msqID'} = $curmsqID++;
					}
					$fh->append($path.'/'.$fn, $js->encode($row) ."\n");
				}

				// -------- dump from btFormTomoacAnswerSet -------- //
				$asIDar = array();
				$sql = "SELECT DISTINCT asID FROM btFormTomoacAnswers WHERE $msqIDlist ORDER BY asID,msqID";
				//error_log($sql,0);
				$rows = $db->query($sql);
				foreach($rows as $row)
					foreach($row as $key=>$val)
						$asIDar[] = $val;	// listup asID

				$newasID = 0;
				foreach($asIDar as $asID) {
					$val = array($asID);
					$sql = "SELECT * FROM btFormTomoacAnswerSet WHERE asID=?";
					$rows = $db->query($sql, $val);

					$row = $rows->fetchrow();
					$row{'asID'} = $newasID;
					$fh->append($path.'/'.$fn, $js->encode($row) ."\n");

					$newasID++;
				}
				// -------- closed ------- //
				if($errmes == '')
					$this->set('message', '"'.$fn.'" '.t('was backuped.'));
				else
					$this->set('message', $errmes);
				break;
			
			case 'download':		// download

				$fn = $path .'/'. $_POST['filename'];
				$fh->forceDownload($fn);
				break;
			
			case 'delete':			// delete
			
				$fn = $path .'/'. $_POST['filename'];
				if(unlink($fn))
					$this->set('message', '"'.$fn.'" '.t('was deleted.'));
				else
					$this->set('message', t('could not delete.'));
				break;
			}
		}
	}
}

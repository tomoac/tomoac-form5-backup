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

			switch( $_POST['function'] ) {
			
			case 'backup':		// backup

				// -------- dump from btFormTomoacQuestions -------- //
				$fn = date('Y-m-d_H:i').'_'.$_POST['surveyName'].'('.$_POST['bID'].').json';
				$fh->clear($path.'/'.$fn);

				$bid = $_POST['bID'];
				$rows = $db->query("SELECT * FROM btFormTomoacQuestions WHERE bID='".$bid."' ORDER BY position,msqID");
				foreach($rows as $row) {
					$fh->append($path.'/'.$fn, $js->encode($row) ."\n");
				}
				$qsfn = $fn;

				// -------- dump from btFormTomoacAnswers -------- //
				$fn = date('Y-m-d_H:i').'_'.$_POST['surveyName'].'('.$_POST['bID'].')_Answers.json';
				$fh->clear($path.'/'.$fn);

				// listup msqID
				$bid = $_POST['bID'];
				$msqIDlist = '';
				$sql = "SELECT msqID FROM btFormTomoacQuestions WHERE bID=$bid ORDER BY position,msqID";
//				error_log($sql,0);
				$rows = $db->query( $sql );
				foreach($rows as $row) {
					foreach($row as $key=>$val) {
						if($msqIDlist != '')
							$msqIDlist .= ' OR ';
						$msqIDlist .= 'msqID='.$val;
					}
				}
				$sql = "SELECT * FROM btFormTomoacAnswers WHERE $msqIDlist ORDER BY asID,msqID";
				error_log($sql,0);
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
				
				// listup asID
				$asIDar = array();
				$sql = "SELECT DISTINCT asID FROM btFormTomoacAnswers WHERE ".$msqIDlist." ORDER BY asID,msqID";
				error_log($sql,0);
				$rows = $db->query($sql);
				foreach($rows as $row)
					foreach($row as $key=>$val)
						$asIDar[] = $val;

				$fn = date('Y-m-d_H:i').'_'.$_POST['surveyName'].'('.$_POST['bID'].')_AnswerSet.json';
				$fh->clear($path.'/'.$fn);

				$newasID = 0;
				foreach($asIDar as $asID) {
					$sql = "SELECT * FROM btFormTomoacAnswerSet WHERE asID=".$asID;
					$rows = $db->query($sql);

					$row = $rows->fetchrow();
					$row{'asID'} = $newasID;
					$fh->append($path.'/'.$fn, $js->encode($row) ."\n");

					$newasID++;
				}

				// -------- closed ------- //
				if($errmes == '')
					$this->set('message', '"'.$qsfn.'" '.t('was backuped.'));
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

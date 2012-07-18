<?php defined('C5_EXECUTE') or die(_("Access Denied.")); ?>

<?php
	$backupdir = "/dashboard/form_tomoac_backup";
	$subname = t('Backup Form Items of \'Tomoac Form 5 Backup\'');
?>
<?php
	/* ---------------- common code (2012/7/18) ---------------- */

	$ver = substr(Config::get('SITE_APP_VERSION'),0,4);	// check current version
	$errmes = '';
	
	if($ver == '5.4.') {
		// version 5.4.x
		echo '<h1><span>'.$subname.'</span></h1>';
		echo '<div class="ccm-dashboard-inner">';
		echo '<div class="ccm-addon-list-wrapper">';
	}
	if($ver == '5.5.') {
		// version 5.5.x
		$h = Loader::helper('concrete/dashboard');
		echo $h->getDashboardPaneHeaderWrapper( $subname );
	}

	$db = Loader::db();
	$js = Loader::helper('json');
	$fh = Loader::helper('file');
	try {
		$sql = "SELECT	CollectionVersions.cID,
						btFormTomoac.surveyName,
						btFormTomoac.bID,
						btFormTomoac.questionSetId,
						CollectionVersions.cvDateCreated
					FROM CollectionVersionBlocks 
						INNER JOIN CollectionVersions 
							ON CollectionVersionBlocks.cID=CollectionVersions.cID 
								AND CollectionVersionBlocks.cvID=CollectionVersions.cvID 
						INNER JOIN btFormTomoac 
							ON CollectionVersionBlocks.bID=btFormTomoac.bID 
					WHERE CollectionVersions.cvIsApproved=1
				";
		$rows = $db->Execute($sql);
	}
	catch (exception $e) {
		$errmes = t('You have not created any forms by \'Tomoac Form 5\'.');
	}
	if($ver == '5.4.') {
		$title = '
			<div class="ccm-spacer">&nbsp;</div>
			<div style="margin:0px; padding:0px; width:100%; height:auto" >	
			<table class="grid-list" width="100%" cellspacing="1" cellpadding="0" border="0">
			<tr>
				<td class="subheader">'.t('Page Name').t(' (cID)').'</td>
				<td class="subheader">'.t('Form Name').t(' (surveyName)').'</td>
				<td class="subheader">'.t('BlockID').t(' (bID)').'</td>
				<td class="subheader">'.t('ItemID').t(' (questionSetId)').'</td>
				<td class="subheader">'.t('Created Date').t(' (created)').'</td>
				<td class="subheader"></td>
			</tr>
		';
	}
	if($ver == '5.5.') {
		$title = '
			<div style="margin:0px; padding:0px; width:100%; height:auto" >
			<div class="ccm-ui">
			<table class="zebra-striped" border="1">
			<tr>
				<td class="header">'.t('Page Name').t(' (cID)').'</td>
				<td class="header">'.t('Form Name').t(' (surveyName)').'</td>
				<td class="header">'.t('BlockID').t(' (bID)').'</td>
				<td class="header">'.t('ItemID').t(' (questionSetId)').'</td>
				<td class="header">'.t('Created Date').t(' (created)').'</td>
				<td class="header"></td>
			</tr>
		';
	}
	$html = '';
	if($errmes == '') {
		foreach($rows as $row) {
			if($html == '')
				echo $title;
			foreach($row as $key=>$val) {
				switch($key) {
				case 'cID':
					$cid = $val;
					$p = Page::getByID( $cid );
					$html.= '<tr>';
					$html.= '<td>'.$p->getCollectionName().'</td>';
					break;
				case 'surveyName':
					$html.= '<td>'.$val.'</td>';
					$surveyName = $val;
					break;
				case 'bID':
					$html.= '<td>'.$val.'</td>';
					$bid = $val;
					break;
				case 'questionSetId':
					$html.= '<td>'.$val.'</td>';
					break;
				case 'cvDateCreated':
					$html.= '<td>'.$val.'</td>';

					$html.= '<form action="'.View::url( $backupdir.'/backup','backup_form').'" method="post">'."\n";
					$html.= '<td>&nbsp;';
					$html.= '<input type="hidden" name="function" value="backup">';
					$html.= '<input type="hidden" name="surveyName" value="'.$surveyName.'">'."\n";
					$html.= '<input type="hidden" name="bID" value="'.$bid.'">'."\n";
					$html.= '<input type="hidden" name="questionSetId" value="'.$val.'">'."\n";
					$html.= '<input type="submit" name="backup" value="'.t('Form Backup').'">'."\n";
					$html.= '<input type="checkbox" name="data" value="data" checked>'.t('with Data')."\n";
					$html.= '</td>';
					$html.= '</form>';
					$html.= '</tr>';
					break;
				}
			}
		}
	}
	if($html != '') {
		$html.= '</table>';
		$html.= '</div>';
		$html.= '</div>';
	} else {
		$html.= t('You have not created any forms by \'Tomoac Form 5\'.');
	}
	echo $html;

?>

<?php
	if($errmes == '') {
		if($ver == '5.4.') {
			echo '
				<div style="margin:0px; padding:0px; width:100%; height:auto" >	
				<br />
				<table class="grid-list" width="100%" cellspacing="1" cellpadding="0" border="0">
			';
		}
		if($ver == '5.5.') {
			echo '
				<div style="margin:0px; padding:0px; width:100%; height:auto" >
				<div class="ccm-ui">
				<table class="zebra-striped">
			';
		}
		$files = $fh->getDirectoryContents( DIR_BASE . "/files/tomoacform5" );
		rsort($files);
		foreach($files as $fn) {
			$html = '';
			$html.= '<tr>';
			$html.= '<td>'. $fn . '</td>';
			$html.= '<form action="'.View::url($backupdir.'/backup','backup_form').'" method="post">'."\n";
			$html.= '<td>';
			$html.= '<input type="hidden" name="function" value="download">';
			$html.= '<input type="hidden" name="filename" value="'.$fn.'">'."\n";
			$html.= '<input type="submit" name="exec" value="'.t('Download').'">'."\n";
			$html.= '</td>';
			$html.= '</form>';
			$html.= '<form action="'.View::url($backupdir.'/backup','backup_form').'" method="post">'."\n";
			$html.= '<td>';
			$html.= '<input type="hidden" name="function" value="delete">';
			$html.= '<input type="hidden" name="filename" value="'.$fn.'">'."\n";
			$html.= '<input type="submit" name="exec" value="'.t('Delete').'">'."\n";
			$html.= '</td>';
			$html.= '</form>';
			$html.= '</tr>';
			echo $html;
		}
	}
?>
		</table>
		</div>
		</div>
	</div>
</div>

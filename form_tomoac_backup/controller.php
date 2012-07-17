<?php

defined('C5_EXECUTE') or die(_("Access Denied."));

class FormTomoacBackupPackage extends Package {

     protected $pkgHandle = 'form_tomoac_backup';
     protected $appVersionRequired = '5.4.2';
     protected $pkgVersion = '0.6.4';

     public function getPackageDescription() {
          return t('Tomoac Form 5 Backup tools.');
     }

     public function getPackageName() {
          return t('Tomoac Form 5 Backup');
     }
     
	public function install() {
		$pkg = parent::install();

		// install block 
		Loader::model('single_page');

		// install pages
		$sp = SinglePage::add('/dashboard/form_tomoac_backup', $pkg);
		$sp->update(array('cName'=>t('Tomoac Form 5 Backup'), 'cDescription'=>t('Tomoac Form 5 Backup')));

		$sp = SinglePage::add('/dashboard/form_tomoac_backup/backup', $pkg);
		$sp->update(array('cName'=>t('Form Backup'), 'cDescription'=>t('Tomoac Form 5 Backup')));

		$sp = SinglePage::add('/dashboard/form_tomoac_backup/restore', $pkg);
		$sp->update(array('cName'=>t('Form Restore'), 'cDescription'=>t('Tomoac Form 5 Restore')));
	}
}

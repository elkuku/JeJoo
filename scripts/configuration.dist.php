<?php
class JConfig
{
    /** A directory outside the web root - accessible by the script */
	public $buildPath  = 'CHANGE ME';

	/* A web acessible target path - not used yet */
	public $targetPath = '';


/* Not used yet */

	public $dbtype		= 'mysql';
	public $host		= '127.0.0.1';
	public $user		= '';
	public $password	= '';
	public $db		= '';
	public $dbprefix	= '';
	public $ftp_host	= '127.0.0.1';
	public $ftp_port	= '21';
	public $ftp_user	= '';
	public $ftp_pass	= '';
	public $ftp_root	= '';
	public $ftp_enable	= 0;
	public $tmp_path	= '';
	public $log_path	= '';
	public $mailer		= 'mail';
	public $mailfrom	= 'admin@localhost.home';
	public $fromname	= '';
	public $sendmail	= '/usr/sbin/sendmail';
	public $smtpauth	= '0';
	public $smtpsecure	= 'none';
	public $smtpport	= '25';
	public $smtpuser	= '';
	public $smtppass	= '';
	public $smtphost	= 'localhost';
	public $debug		= 0;
	public $caching		= '0';
	public $cachetime	= '900';
	public $language	= 'en-GB';
	public $secret		= null;
	public $editor		= 'none';
	public $offset		= 0;
	public $lifetime	= 15;
}

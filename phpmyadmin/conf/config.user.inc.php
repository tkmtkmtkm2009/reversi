<?php

	/* Servers configuration */
	$i = 1;

	/* Server: localhost [1] */
	$i++;
	$cfg['Servers'][$i]['auth_type'] = 'cookie';
	$cfg['Servers'][$i]['verbose'] = '開発';
	$cfg['Servers'][$i]['host'] = 'mysql';
	$cfg['Servers'][$i]['connect_type'] = 'tcp';
	$cfg['Servers'][$i]['compress'] = false;
	$cfg['Servers'][$i]['AllowNoPassword'] = false;

	$i++;
	$cfg['Servers'][$i]['auth_type'] = 'cookie';
	$cfg['Servers'][$i]['verbose'] = '検証';
	$cfg['Servers'][$i]['host'] = 'mysql';
	$cfg['Servers'][$i]['connect_type'] = 'tcp';
	$cfg['Servers'][$i]['compress'] = false;
	$cfg['Servers'][$i]['AllowNoPassword'] = false;

?>
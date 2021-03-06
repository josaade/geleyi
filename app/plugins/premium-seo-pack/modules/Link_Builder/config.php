<?php
/**
 * Social_Stats Config file, return as json_encode
 * http://www.aa-team.com
 * ======================
 *
 * @author		Andrei Dinca, AA-Team
 * @version		1.0
 */
 echo json_encode(
	array(
		'Link_Builder' => array(
			'version' => '1.0',
			'menu' => array(
				'order' => 23,
				'title' => __('Link Builder', $psp->localizationName)
				,'icon' => 'assets/menu_icon.png'
			),
			'in_dashboard' => array(
				'icon' 	=> 'assets/32.png',
				'url'	=> admin_url('admin.php?page=' . $psp->alias . "_Link_Builder")
			),
			'desciption' => "You can create list of keywords and URLs, and they will automatically be created",
			'module_init' => 'init.php'
		)
	)
 );
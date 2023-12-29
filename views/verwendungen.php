<?php
	$includesArray = array(
		'title' => 'Verwendungen',
		'axios027' => true,
		'bootstrap5' => true,
		'fontawesome6' => true,
		'vue3' => true,
		'primevue3' => true,
		'navigationcomponent' => true,
		'filtercomponent' => true,
		'tabulator5' => true,
		'customJSs' => array('vendor/vuejs/vuedatepicker_js/vue-datepicker.iife.js'),
		'customJSModules' => array(
			'public/extensions/FHC-Core-BIS/js/apps/Personalmeldung/Verwendungen.js'
		),
		'customCSSs' => array('vendor/vuejs/vuedatepicker_css/main.css')
	);

	$this->load->view('templates/FHC-Header', $includesArray);
?>
	<div id="main">
		<verwendungen></verwendungen>
	</div>

<?php $this->load->view('templates/FHC-Footer', $includesArray); ?>

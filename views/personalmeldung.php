<?php
	$includesArray = array(
		'title' => 'Personalmeldung',
		'axios027' => true,
		'bootstrap5' => true,
		'fontawesome6' => true,
		'vue3' => true,
		'filtercomponent' => true,
		'navigationcomponent' => true,
		'tabulator5' => true,
		'customJSModules' => array(
			'public/extensions/FHC-Core-BIS/js/apps/Personalmeldung/Personalmeldung.js'
		)
	);

	$this->load->view('templates/FHC-Header', $includesArray);
?>
	<div id="main">

		<!-- Navigation component -->
		<core-navigation-cmpt></core-navigation-cmpt>

		<!-- Personalmeldung component -->
		<personalmeldung></personalmeldung>

	</div>

<?php $this->load->view('templates/FHC-Footer', $includesArray); ?>

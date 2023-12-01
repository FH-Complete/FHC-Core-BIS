<?php
	$includesArray = array(
		'title' => 'Personalmeldung',
		'axios027' => true,
		'bootstrap5' => true,
		'fontawesome6' => true,
		'vue3' => true,
		'filtercomponent' => true,
		'navigationcomponent' => true,
		'customJSModules' => array(
			'public/extensions/FHC-Core-BIS/js/apps/Personalmeldung/Plausichecks.js'
		)
	);

	$this->load->view('templates/FHC-Header', $includesArray);
?>
	<div id="main">
		<plausichecks></plausichecks>
	</div>

<?php $this->load->view('templates/FHC-Footer', $includesArray); ?>

<?php
	$includesArray = array(
		'title' => 'Bismeldestichtage',
		'axios027' => true,
		'bootstrap5' => true,
		'fontawesome6' => true,
		'vue3' => true,
		'filtercomponent' => true,
		'navigationcomponent' => true,
		'tabulator5' => true,
		'customJSModules' => array('public/extensions/FHC-Core-BIS/js/apps/Personalmeldung/Personalmeldung.js')
	);

	$this->load->view('templates/FHC-Header', $includesArray);
?>
	<div id="main">

		<!-- Navigation component -->
		<core-navigation-cmpt></core-navigation-cmpt>

		<div id="content">
			<header>
				<h1 class="h2 fhc-hr"><?php echo $this->p->t('personalmeldung', 'personalmeldung') ?></h1>
			</header>
			<!-- input fields -->
			<div class="row">
				<div class="col-6">
					<select class="form-select" name="studiensemester_kurzbz" v-model="currSem">
						<option v-for="sem in semList" :value="sem.studiensemester_kurzbz">
							{{ sem.studiensemester_kurzbz }}
						</option>
					</select>
				</div>
				<div class="col-3">
					<button type="button" class="btn btn-primary" @click="getMitarbeiter">
						<?php echo $this->p->t('personalmeldung', 'mitarbeiterdatenAnzeigen') ?>
					</button>
				</div>
				<div class="col-3">
					<button type="button" class="btn btn-primary" @click="downloadPersonalmeldungXml">
						<?php echo $this->p->t('personalmeldung', 'xmlExportieren') ?>
					</button>
				</div>
			</div>
			<br />
			<!-- Filter component -->
			<div class="row">
				<div class="col">
					<core-filter-cmpt
						ref="personalmeldungTable"
						:side-menu="false"
						:tabulator-options="personalmeldungTabulatorOptions"
						:table-only="true">
					</core-filter-cmpt>
				</div>
			</div>
		</div>
	</div>

<?php $this->load->view('templates/FHC-Footer', $includesArray); ?>

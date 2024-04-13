<?php
	$includesArray = array(
		'title' => 'Filevergleich',
		'bootstrap5' => true,
		'fontawesome6' => true,
		'navigationcomponent' => true
	);

	$this->load->view('templates/FHC-Header', $includesArray);
?>
	<div id="main">
		<div id="content">
			<header>
				<h1 class="h2 fhc-hr">Personalmeldung File Vergleich</h1>
			</header>
			<br>
			<form action="<?php echo site_url("extensions/FHC-Core-BIS/PersonalmeldungFileVergleich/compareFile")?>" class="form-inline">
				<div class="row">
					<div class="col-8">
						<select class="form-select" name="studiensemester_kurzbz">
							<?php foreach ($semList as $studiensemester): ?>
							<option<?php if ($studiensemester->studiensemester_kurzbz == $currSem) echo ' selected'; ?>>
								<?php echo $studiensemester->studiensemester_kurzbz ?>
							</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col-4">
						<button type="submit" class="btn btn-primary">Vergleichen</button>
					</div>
				</div>
			</form>
		</div>
	</div>

<?php $this->load->view('templates/FHC-Footer', $includesArray); ?>

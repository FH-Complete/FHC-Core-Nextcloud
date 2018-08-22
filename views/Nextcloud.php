<?php
	$this->load->view(
		'templates/FHC-Header',
		array(
			'title' => 'Nextcloud',
			'jquery' => true,
			'jqueryui' => true,
			'bootstrap' => true,
			'fontawesome' => true,
			'sbadmintemplate' => true,
			'ajaxlib' => true,
			'navigationwidget' => true,
			'customJSs' =>
				array(
					'public/extensions/FHC-Core-Nextcloud/js/nextcloud.js',
				)
		)
	);
?>

<body>
	<div id="wrapper">

		<?php echo $this->widgetlib->widget('NavigationWidget'); ?>

		<div id="page-wrapper">
			<div class="container-fluid">
				<div class="row">
					<div class="col-lg-12">
						<h3 class="page-header">Nextcloud Synchronisation</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<form role="form" method="post" action="<?php echo site_url('extensions/FHC-Core-Nextcloud/Nextcloud/addLehrveranstaltungGroupsByParams')?>">
							<div class="form-group">
								<label>Studiensemester</label>
								<select class="form-control" id="studiensemester" name="studiensemester" >
									<?php foreach ($studiensemester as $sem): ?>
										<?php $selected = ($sem->studiensemester_kurzbz === $studiensemester_kurzbz) ? 'selected' : ''; ?>
										<option value="<?php echo $sem->studiensemester_kurzbz ?>" <?php echo $selected ?>><?php echo $sem->studiensemester_kurzbz ?></option>
									<?php endforeach;?>
								</select>
							</div>
							<div class="form-group">
								<label>Studiengang</label>
								<select class="form-control" id="studiengang_kz" name="studiengang_kz" >
									<?php foreach ($studiengaenge as $studiengang): ?>
										<option value="<?php echo $studiengang->studiengang_kz?>"><?php echo $studiengang->kuerzel.' - '.$studiengang->bezeichnung ?></option>
									<?php endforeach;?>
								</select>
							</div>
							<div class="form-group">
								<label>Ausbildungssemester</label>
								<select class="form-control" id="ausbildungssemester" name="ausbildungssemester" >
								</select>
							</div>
							<div class="form-group">
								<label>Lehrveranstaltungen</label>
								<select multiple="" class="form-control" id="lvids" name="lvids[]" size="20">
								</select>
							</div>
							<div class="form-group">
								<div class="checkbox">
									<label>
										<input type="checkbox" name="syncusers">
										&nbsp;Studierenden/Lektoren der Lehrveranstaltung synchronisieren
									</label>
								</div>
							</div>
							<div class="form-group">
								<button class="btn btn-default" type="submit" id="initsync">Gruppe(n) synchronisieren</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</body>

<?php $this->load->view('templates/FHC-Footer'); ?>


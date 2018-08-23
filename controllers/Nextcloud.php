<?php
/* Copyright (C) 2018 fhcomplete.org
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, USA.
 *
 * Authors: Andreas Oesterreicher <andreas.oesterreicher@technikum-wien.at>,
 */
if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Handles manual Nextcloud Sync of lvs and corresponding users
 */
class Nextcloud extends Auth_Controller
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(array(
			'index'=>'admin:rw',
			'addLehrveranstaltungGroupsByParams'=>'admin:rw',
			'addAllLehrveranstaltungGroups'=>'admin:rw',
			'getLehrveranstaltungGroupStrings'=>'admin:r',
			'getAusbildungssemesterByStudiensemesterAndStudiengang'=>'admin:r'
			)
		);

		$this->config->load('extensions/FHC-Core-Nextcloud/config');

		$this->load->model('organisation/Studiensemester_model', 'StudiensemesterModel');
		$this->load->model('organisation/Studiengang_model', 'StudiengangModel');

		$this->load->library('extensions/FHC-Core-Nextcloud/NextcloudSyncLib');
	}

	/**
	 * Index Controller
	 * Initializes GUI with necessary data
	 * @return void
	 */
	public function index()
	{
		$this->load->library('WidgetLib');

		$this->StudiensemesterModel->addSelect('studiensemester_kurzbz');
		$this->StudiensemesterModel->addOrder('start', 'DESC');
		$studiensemesterdata = $this->StudiensemesterModel->load();

		if (isError($studiensemesterdata))
			show_error($studiensemesterdata->retval);

		$currstudiensemesterdata = $this->StudiensemesterModel->getAktOrNextSemester();

		if (isError($currstudiensemesterdata))
			show_error($currstudiensemesterdata->retval);

		$studiensemester_kurzbz = $currstudiensemesterdata->retval[0]->studiensemester_kurzbz;

		$studiengangdata = $this->StudiengangModel->getStudiengaengeByStudiensemester($studiensemester_kurzbz);

		if (isError($studiengangdata))
			show_error($studiengangdata->retval);

		$studiengaenge = array();

		foreach ($studiengangdata->retval as $studiengang)
		{
			$studiengangobj = new stdClass();
			$studiengangobj->studiengang_kz = $studiengang->studiengang_kz;
			$studiengangobj->kuerzel = $studiengang->kuerzel;
			$studiengangobj->bezeichnung= $studiengang->bezeichnung;
			$studiengaenge[] = $studiengangobj;
		}

		$data = array(
			'studiensemester' => $studiensemesterdata->retval,
			'studiensemester_kurzbz' => $studiensemester_kurzbz,
			'studiengaenge' => $studiengaenge
		);

		$this->load->view('extensions/FHC-Core-Nextcloud/Nextcloud', $data);
	}

	/**
	 * Gets unique names of LV-groups and returns them as JSON
	 */
	public function getLehrveranstaltungGroupStrings()
	{
		$this->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');

		$studiensemester_kurzbz = $this->input->post('studiensemester');
		$studiengang_kz = $this->input->post('studiengang_kz');
		$ausbildungssemester = $this->input->post('ausbildungssemester');

		$result = $this->LehrveranstaltungModel->getLehrveranstaltungGroupNames($studiensemester_kurzbz, $ausbildungssemester, $studiengang_kz);

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($result));
	}

	/**
	 * Gets Ausbildungssemester based on Studiensemester and Studiengang
	 */
	public function getAusbildungssemesterByStudiensemesterAndStudiengang()
	{
		$studiensemester_kurzbz = $this->input->post('studiensemester');
		$studiengang_kz = $this->input->post('studiengang_kz');

		$result = $this->StudiensemesterModel->getAusbildungssemesterByStudiensemesterAndStudiengang($studiensemester_kurzbz, $studiengang_kz);

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($result));
	}

	public function addAllLehrveranstaltungGroups($studiensemester_kurzbz = null, $syncusers = true)
	{
		//TODO regex for studsem?
		if (!isset($studiensemester_kurzbz))
		{
			$currstudiensemesterdata = $this->StudiensemesterModel->getAktOrNextSemester();

			if (!hasData($currstudiensemesterdata))
				show_error('no studiensemester retrieved');

			$studiensemester_kurzbz = $currstudiensemesterdata->retval[0]->studiensemester_kurzbz;
		}

		$this->nextcloudsynclib->addLehrveranstaltungGroups($studiensemester_kurzbz, null, null, null, $syncusers);
	}

	/**
	 * Intitializes lv-group sync using post parameters
	 */
	public function addLehrveranstaltungGroupsByParams()
	{
		$studiensemester_kurzbz = $this->input->post('studiensemester');
		$lehrveranstaltung_ids = $this->input->post('lvids');
		$ausbildungssemester = $this->input->post('ausbildungssemester');
		$studiengang_kz = $this->input->post('studiengang_kz');
		$syncusers = $this->input->post('syncusers');
		$syncusers = isset($syncusers) ? true : false;

		$this->nextcloudsynclib->addLehrveranstaltungGroups($studiensemester_kurzbz, $ausbildungssemester, $studiengang_kz, $lehrveranstaltung_ids, $syncusers);
	}
}

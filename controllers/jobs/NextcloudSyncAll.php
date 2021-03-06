<?php

if (! defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * Cronjob for syncing all lvs and their users with Nextcloud for one semester
 *
 */

class NextcloudSyncAll extends FHC_Controller
{

	/**
	 * constructor
	 */
	public function __construct()
	{
		parent::__construct();

		if (!$this->input->is_cli_request())
		{
			$this->output->set_status_header(403, 'Jobs must be run from the CLI');
			echo "Jobs must be run from the CLI";
			exit;
		}

		$this->load->library('extensions/FHC-Core-Nextcloud/NextcloudSyncLib');
	}

	/**
	 * Main function index as help
	 *
	 * @return	void
	 */
	public function index()
	{
		$result = "The following are the available command line interface commands\n\n";
		$result .= "php index.ci.php extensions/FHC-Core-Nextcloud/jobs/NextcloudSyncAll RunAll";

		echo $result.PHP_EOL;
	}

	/**
	 * Runs sync for lvs (in current/next studiensemester) and oes (active)
	 */
	public function runAll($splitsize=1, $part=1)
	{
		// Sync lv groups
		$this->runLvGroups(null, true, $splitsize, $part);

		// Sync oe groups
		$this->runOeGroups($splitsize, $part);
	}

	/**
	 * Initializes sync for all lvs of all Studiengaenge for a given Studiensemester
	 * @param null $studiensemester_kurzbz if not given, actual or next (in summer) semester is retrieved
	 * @param bool $syncusers wether to sync students and lecturers of the group
	 * @param int $splitsize number of chunks to split into when parallel processing
	 * @param int $part number of the chunk needed after split
	 */
	public function runLvGroups($studiensemester_kurzbz = null, $syncusers = true, $splitsize=1, $part=1)
	{
		$studiensemester_kurzbz_arr = $this->_getAktOrNextSemester($studiensemester_kurzbz);

		foreach ($studiensemester_kurzbz_arr as $studiensemester)
		{
			$this->nextcloudsynclib->addLehrveranstaltungGroups($studiensemester, null, null, null, $syncusers, $splitsize, $part);
		}
	}

	/**
	 * Initializes Oe group sync
	 */
	public function runOeGroups($syncusers, $splitsize=1, $part=1)
	{
		$this->nextcloudsynclib->addOeGroups($syncusers, $splitsize, $part);
	}

	/**
	 * Initializes deletion for all lvs of all Studiengaenge for a given Studiensemester
	 * @param null $studiensemester_kurzbz
	 */
	public function deleteLvGroups($studiensemester_kurzbz)
	{
		$this->nextcloudsynclib->deleteLehrveranstaltungGroups($studiensemester_kurzbz);
	}

	/**
	 * Retrieves current, in summer current and next Studiensemester.
	 * @param $studiensemester_kurzbz
	 * @return array studiensemester_kurzbz
	 */
	private function _getAktOrNextSemester($studiensemester_kurzbz)
	{
		$this->load->model('organisation/Studiensemester_model', 'StudiensemesterModel');

		if (!isset($studiensemester_kurzbz) || !preg_match("/^[W|S]S\d{4,}$/", $studiensemester_kurzbz))
		{
			$aktornextsemdata = $this->StudiensemesterModel->getAktOrNextSemester();
			$aktsemdata = $this->StudiensemesterModel->getAktOrNextSemester(0);

			if (!hasData($aktornextsemdata) || !hasData($aktsemdata))
				show_error('no studiensemester retrieved');

			$aktornextsem = $aktornextsemdata->retval[0]->studiensemester_kurzbz;
			$aktsem = $aktsemdata->retval[0]->studiensemester_kurzbz;

			$semarr = array($aktornextsem);
			if ($aktsem !== $aktornextsem)
				$semarr[] = $aktsem;

			return $semarr;
		}
		return array($studiensemester_kurzbz);
	}
}

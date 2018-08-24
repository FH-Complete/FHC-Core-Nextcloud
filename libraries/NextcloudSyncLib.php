<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Library for syncing lvs and lv-users with Nextcloud
 */
class NextcloudSyncLib
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		// Gets CI instance
		$this->ci =& get_instance();
		$this->ci->load->model('extensions/FHC-Core-Nextcloud/Ocs_Model', 'OcsModel');

		if ($this->ci->input->is_cli_request())
		{
			$this->nl = PHP_EOL;
		}
		else
		{
			$this->nl = '<br />';
		}
	}

	/**
	 * Adds (syncs) Lehrveranstaltung Groups to Nextcloud
	 * @param $studiensemester_kurzbz
	 * @param null $ausbildungssemester
	 * @param null $studiengang_kz
	 * @param null $lehrveranstaltung_ids
	 * @param bool $syncusers wether to add the users of lv after creating group
	 */
	public function addLehrveranstaltungGroups($studiensemester_kurzbz, $ausbildungssemester = null, $studiengang_kz = null, $lehrveranstaltung_ids = null, $syncusers = true)
	{
		$this->ci->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');

		$groupdata = $this->ci->LehrveranstaltungModel->getLehrveranstaltungGroupNames($studiensemester_kurzbz, $ausbildungssemester, $studiengang_kz, $lehrveranstaltung_ids);

		echo 'NEXTCLOUD LEHRVERANSTALTUNGEN SYNC';
		echo $this->nl.str_repeat('-', 50);

		$groupsadded = $usersadded = $usersremoved = 0;

		if (hasData($groupdata))
		{
			foreach ($groupdata->retval as $group)
			{
				$lehrveranstaltung_id = $group->lehrveranstaltung_id;
				$groupname = $group->lvgroupname;

				echo $this->nl;

				//TODO check if failed because group already exists
				if ($this->ci->OcsModel->addGroup($groupname))
				{
					echo 'ok, lv group '.$groupname.' created';
					$groupsadded++;
				}
				else
					echo 'creation of lv group '.$groupname.' failed';

				if (isset($syncusers) && $syncusers === true)
				{
					$syncedusers = $this->addUsersToLvGroup($studiensemester_kurzbz, $lehrveranstaltung_id);
					$usersadded += $syncedusers[0];
					$usersremoved += $syncedusers[1];
				}
			}
		}
		else
		{
			echo $this->nl.'no lv groups found in source system';
		}

		echo $this->nl.str_repeat('-', 50);
		echo $this->nl.'SYNC FINISHED. ALTOGETHER: '.$groupsadded.' LVs added, '.$usersadded.' users added, '.$usersremoved.' users removed';
		echo $this->nl.str_repeat('-', 50);
		echo $this->nl.'NEXTCLOUD LEHRVERANSTALTUNGEN SYNC END'.$this->nl;
	}

	/**
	 * Adds users (students + lecturers) to an existing group in Nextcloud. Group name is generated with same method as when adding groups.
	 * @param $studiensemester_kurzbz
	 * @param $lehrveranstaltung_id
	 */
	public function addUsersToLvGroup($studiensemester_kurzbz, $lehrveranstaltung_id)
	{
		$this->ci->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');

		$groupdata = $this->ci->LehrveranstaltungModel->getLehrveranstaltungGroupNames($studiensemester_kurzbz, null, null, $lehrveranstaltung_id);

		if (isError($groupdata))
			show_error($groupdata->retval);

		if (count($groupdata->retval) == 1)
			$groupname = $groupdata->retval[0]->lvgroupname;
		else
		{
			echo $this->nl.'wrong number of lv groups';
			return;
		}

		$lecturerdata = $this->ci->LehrveranstaltungModel->getLecturersByLv($studiensemester_kurzbz, $lehrveranstaltung_id);

		if (isError($lecturerdata))
			show_error($lecturerdata->retval);

		$studentdata = $this->ci->LehrveranstaltungModel->getStudentsByLv($studiensemester_kurzbz, $lehrveranstaltung_id);

		if (isError($studentdata))
			show_error($studentdata->retval);

		$userdata = array_merge($lecturerdata->retval, $studentdata->retval);

		return $this->_syncUsers($userdata, $groupname);
	}

	/**
	 * Adds (syncs) Oe Groups to Nextcloud
	 */
	public function addOeGroups()
	{
		$this->ci->load->model('organisation/Organisationseinheit_model', 'OrganisationseinheitModel');
		$this->ci->load->model('person/Benutzerfunktion_model', 'BenutzerfunktionModel');

		$this->ci->OrganisationseinheitModel->addSelect('oe_kurzbz');
		$oes = $this->ci->OrganisationseinheitModel->loadWhere(array('aktiv' => true));

		if (isError($oes))
			show_error($oes->retval);

		echo 'NEXTCLOUD ORGANISATIONSEINHEITEN SYNC';
		echo $this->nl.str_repeat('-', 50);

		$groupsadded = $usersadded = $usersremoved = 0;

		$cnt = 0;

		if (hasData($oes))
		{
			foreach ($oes->retval as $oe)
			{
				$oe_kurzbz = $oe->oe_kurzbz;

				$benutzer = $this->ci->BenutzerfunktionModel->getByOeAndFunktion($oe->oe_kurzbz, array('Leitung', 'oezuordnung'));

				echo $this->nl;

				if (hasData($benutzer))
				{
					if ($this->ci->OcsModel->addGroup($oe_kurzbz))
					{
						echo 'ok, oe group '.$oe_kurzbz.' created';
						$groupsadded++;
					}
					else
						echo 'creation of oe group '.$oe_kurzbz.' failed';
				}
				else
				{
					echo 'no user for oe group '.$oe_kurzbz.' - skipping creation';
				}
				$syncedusers = $this->_syncUsers($benutzer->retval, $oe_kurzbz);
				$usersadded += $syncedusers[0];
				$usersremoved += $syncedusers[1];
				$cnt++;
				if ($cnt > 50)
					break;
			}
		}
		else
		{
			echo $this->nl.'no oes found in source system';
		}

		echo $this->nl.str_repeat('-', 50);
		echo $this->nl.'SYNC FINISHED. ALTOGETHER: '.$groupsadded.' OES added, '.$usersadded.' users added, '.$usersremoved.' users removed';
		echo $this->nl.str_repeat('-', 50);
		echo $this->nl.'NEXTCLOUD ORGANISATIONSEINHEITEN SYNC END'.$this->nl;
	}

	/**
	 * Syncs users of a group to nextcloud group
	 * Adds users if not in Nextcloud, removes users from Nextcloud group if not in source system
	 * @param $userstoadd
	 * @param $groupname
	 * @return array indicating how many users were added [0] and removed [1]
	 */
	private function _syncUsers($userstoadd, $groupname)
	{
		$nextcloudusers = $this->ci->OcsModel->getGroupMember($groupname);

		$usersadded = $usersremoved = 0;
		if (is_array($nextcloudusers))
		{
			$uid_arr = array();

			for ($i = 0; $i < count($userstoadd); $i++)
			{
				echo $this->nl;

				$uid = $userstoadd[$i]->uid;
				$uid_arr[] = $uid;

				if (in_array($uid, $nextcloudusers))
					echo 'user with uid '.$uid.' already exists in group '.$groupname;
				else
				{
					if ($this->ci->OcsModel->addUserToGroup($groupname, $uid))
					{
						echo 'ok, user with uid '.$uid.' added to group '.$groupname;
						$usersadded++;
					}
					else
						echo 'adding user with uid '.$uid.' to group '.$groupname.' failed';
				}
			}

			$userstoremove = array_diff($nextcloudusers, $uid_arr);

			foreach ($userstoremove as $user)
			{
				echo $this->nl.'user in Nextcloud group but not in source system group - removing '.$user.' from '.$groupname;

				echo $this->nl;
				if ($this->ci->OcsModel->removeUserFromGroup($groupname, $user))
				{
					echo 'user removed from group!';
					$usersremoved++;
				}
				else
				{
					echo 'user removal failed!';
				}
			}
		}
		else
			echo $this->nl.'Nextcloudusers could not be retrieved!';

		echo $this->nl.$groupname.' done, '.$usersadded.' users added, '.$usersremoved.' users removed.'.$this->nl;

		return array($usersadded, $usersremoved);
	}
}

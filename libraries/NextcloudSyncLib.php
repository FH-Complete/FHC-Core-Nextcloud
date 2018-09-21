<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Library for syncing lv- and oe-groups with users to Nextcloud
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
		$this->ci->load->model('extensions/FHC-Core-Nextcloud/Ocs_Httpful_Model', 'OcsModel');

		$config = $this->ci->config->item('FHC-Core-Nextcloud');
		$this->debugmode = $config['debugmode'];
		$this->funktionenToSync = array('Leitung', 'oezuordnung');

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
	 * @param int $splitsize number of chunks to split into when parallel processing
	 * @param int $part number of the chunk needed after split
	 */
	public function addLehrveranstaltungGroups($studiensemester_kurzbz, $ausbildungssemester = null, $studiengang_kz = null, $lehrveranstaltung_ids = null, $syncusers = true, $splitsize = 1, $part = 1)
	{
		$nextcloudgroups =  $this->ci->OcsModel->getGroups();

		if (!$nextcloudgroups)
		{
			echo 'Nextcloudgroups could not be retrieved!';
			return;
		}

		$this->ci->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');

		$groupdata = $this->ci->LehrveranstaltungModel->getLehrveranstaltungGroupNames($studiensemester_kurzbz, $ausbildungssemester, $studiengang_kz, $lehrveranstaltung_ids);

		if (!hasData($groupdata))
		{
			echo $this->nl.'no lv groups found in source system';
			return;
		}

		$groups = $groupdata->retval;

		// split into groups when parallel processing
		$groups = $this->_splitGroups($groups, $splitsize, $part);

		echo $this->nl.'NEXTCLOUD LEHRVERANSTALTUNGEN SYNC PART '.$part.'/'.$splitsize;
		echo $this->nl.str_repeat('-', 50);

		$results = array(
			'groupsadded' => 0, 'groupsaddfailed' => 0, 'usersadded' => 0,
			'usersremoved' => 0, 'usersaddfailed' => 0,
			'usersremovefailed' => 0
		);

		$starttime = new DateTime(date('d.m.Y H:i:s'));

		foreach ($groups as $group)
		{
			$lehrveranstaltung_id = $group->lehrveranstaltung_id;
			$groupname = $group->lvgroupname;

			if (in_array($groupname, $nextcloudgroups))
			{
				if ($this->debugmode)
					echo $this->nl.'group '.$groupname.' already exists';
			}
			else
			{
				if ($this->ci->OcsModel->addGroup($groupname))
				{
					if ($this->debugmode)
						echo $this->nl.'ok, lv group '.$groupname.' created';
					$results['groupsadded']++;
				}
				else
				{
					echo $this->nl.'creation of lv group '.$groupname.' failed';
					$results['groupsaddfailed']++;
				}
			}

			if (isset($syncusers) && ($syncusers === true || strtolower($syncusers) === 'true' || $syncusers === '1'))
			{
				$syncedusers = $this->addUsersToLvGroup($studiensemester_kurzbz, $lehrveranstaltung_id);
				$results['usersadded'] += $syncedusers[0];
				$results['usersremoved'] += $syncedusers[1];
				$results['usersaddfailed'] += $syncedusers[2];
				$results['usersremovefailed'] += $syncedusers[3];
			}
		}

		$endtime = new DateTime(date('d.m.Y H:i:s'));

		$this->_printSyncFooter('lehrveranstaltungen', $results, $starttime, $endtime, $splitsize, $part);
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
			echo $this->nl.'wrong number of lv groups for lvid '.$lehrveranstaltung_id;
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
		$nextcloudgroups = $this->ci->OcsModel->getGroups();

		if (!$nextcloudgroups)
		{
			echo 'Nextcloudgroups could not be retrieved!';
			return;
		}

		$this->ci->load->model('organisation/Organisationseinheit_model', 'OrganisationseinheitModel');
		$this->ci->load->model('person/Benutzerfunktion_model', 'BenutzerfunktionModel');

		$this->ci->OrganisationseinheitModel->addSelect('oe_kurzbz');
		$this->ci->OrganisationseinheitModel->addOrder('oe_kurzbz');
		$oes = $this->ci->OrganisationseinheitModel->loadWhere(array('aktiv' => true));

		echo 'NEXTCLOUD ORGANISATIONSEINHEITEN SYNC';
		echo $this->nl.str_repeat('-', 50);

		$results = array(
			'groupsadded' => 0, 'groupsaddfailed' => 0, 'usersadded' => 0,
			'usersremoved' => 0, 'usersaddfailed' => 0,
			'usersremovefailed' => 0
		);

		$starttime = new DateTime(date('d.m.Y H:i:s'));

		if (hasData($oes))
		{
			foreach ($oes->retval as $oe)
			{
				$oe_kurzbz = $oe->oe_kurzbz;

				$benutzer = $this->ci->BenutzerfunktionModel->getBenutzerFunktionen($this->funktionenToSync, $oe_kurzbz, false, true);

				if (isError($benutzer))
					show_error($benutzer->retval);

				$syncusrs = true;

				if (in_array($oe_kurzbz, $nextcloudgroups))
				{
					if ($this->debugmode)
						echo $this->nl.'group '.$oe_kurzbz.' already exists';
				}
				else
				{
					if (hasData($benutzer))
					{
						if ($this->ci->OcsModel->addGroup($oe_kurzbz))
						{
							if ($this->debugmode)
								echo $this->nl.'ok, oe group '.$oe_kurzbz.' created';
							$results['groupsadded']++;
						}
						else
						{
							echo $this->nl.'creation of oe group '.$oe_kurzbz.' failed';
							$results['groupsaddfailed']++;
						}
					}
					else
					{
						if ($this->debugmode)
							echo $this->nl.'no user for oe group '.$oe_kurzbz.' - skipping creation'.$this->nl;
						$syncusrs = false;
					}
				}

				if ($syncusrs)
				{
					$syncedusers = $this->_syncUsers($benutzer->retval, $oe_kurzbz);
					$results['usersadded'] += $syncedusers[0];
					$results['usersremoved'] += $syncedusers[1];
					$results['usersaddfailed'] += $syncedusers[2];
					$results['usersremovefailed'] += $syncedusers[3];
				}
			}
		}
		else
		{
			echo $this->nl.'no oes found in source system';
		}

		$endtime = new DateTime(date('d.m.Y H:i:s'));

		$this->_printSyncFooter('ORGANISATIONSEINHEITEN', $results, $starttime, $endtime);
	}

	/**
	 * Deletes Lehrveranstaltung Groups in Nextcloud
	 * @param $studiensemester_kurzbz
	 * @param null $ausbildungssemester
	 * @param null $studiengang_kz
	 * @param null $lehrveranstaltung_ids
	 */
	public function deleteLehrveranstaltungGroups($studiensemester_kurzbz, $ausbildungssemester = null, $studiengang_kz = null, $lehrveranstaltung_ids = null)
	{
		$nextcloudgroups =  $this->ci->OcsModel->getGroups();
		if (!$nextcloudgroups)
		{
			echo 'Nextcloudgroups could not be retrieved!';
			return;
		}

		$this->ci->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');

		$groupdata = $this->ci->LehrveranstaltungModel->getLehrveranstaltungGroupNames($studiensemester_kurzbz, $ausbildungssemester, $studiengang_kz, $lehrveranstaltung_ids);

		echo 'NEXTCLOUD LEHRVERANSTALTUNGEN DELETION';
		echo $this->nl.str_repeat('-', 50);

		$groupsdeleted = 0;

		if (hasData($groupdata))
		{
			foreach ($groupdata->retval as $group)
			{
				$groupname = $group->lvgroupname;

				echo $this->nl;

				if (in_array($groupname, $nextcloudgroups))
				{
					if ($this->ci->OcsModel->deleteGroup($groupname))
					{
						echo 'ok, lv group '.$groupname.' deleted';
						$groupsdeleted++;
					}
					else
						echo 'deletion of lv group '.$groupname.' failed';
				}
				else
				{
					echo 'group '.$groupname.' does not exist';
				}
			}
		}
		else
		{
			echo $this->nl.'no lv groups found in source system';
		}

		echo $this->nl.str_repeat('-', 50);
		echo $this->nl.'DELETION FINISHED. ALTOGETHER: '.$groupsdeleted.' LVs deleted';
		echo $this->nl.str_repeat('-', 50);
		echo $this->nl.'NEXTCLOUD LEHRVERANSTALTUNGEN DELETION END'.$this->nl;
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

		$usersadded = $usersremoved = $usersaddfailed = $usersremovefailed = 0;

		if (is_array($nextcloudusers))
		{
			$uid_arr = array();

			foreach ($userstoadd as $user)
			{
				$uid = $user->uid;
				$uid_arr[] = $uid;

				if (in_array($uid, $nextcloudusers))
				{
					if ($this->debugmode)
						echo $this->nl.'user with uid '.$uid.' already exists in group '.$groupname;
				}
				else
				{
					if ($this->ci->OcsModel->addUserToGroup($groupname, $uid))
					{
						if ($this->debugmode)
							echo $this->nl.'ok, user with uid '.$uid.' added to group '.$groupname;
						$usersadded++;
					}
					else
					{
						if ($this->debugmode)
							echo $this->nl.'first add failed to group '.$groupname.', searching for user '.$uid.'...';
						$user = $this->ci->OcsModel->searchUser($uid);

						if (!is_array($user) || empty($user))
						{
							echo $this->nl.'user with uid '.$uid.' not found in Nextcloud';
							$usersaddfailed++;
							continue;
						}

						if ($this->ci->OcsModel->addUserToGroup($groupname, $uid))
						{
							if ($this->debugmode)
								echo $this->nl.'ok, user with uid '.$uid.' added to group '.$groupname.' after search';
							$usersadded++;
						}
						else
						{
							echo $this->nl.'adding user with uid '.$uid.' to group '.$groupname.' failed';
							$usersaddfailed++;
						}
					}
				}
			}

			$userstoremove = array_diff($nextcloudusers, $uid_arr);

			foreach ($userstoremove as $user)
			{
				if ($this->ci->OcsModel->removeUserFromGroup($groupname, $user))
				{
					if ($this->debugmode)
						echo $this->nl.'user '.$user.' removed from group '.$groupname;
					$usersremoved++;
				}
				else
				{
					echo $this->nl.'removal of user '.$user.' from group '.$groupname.' failed';
					$usersremovefailed++;
				}
			}
		}
		else
			echo $this->nl.'Nextcloudusers could not be retrieved for group '.$groupname;

		if ($this->debugmode)
			echo $this->nl.$groupname.' done, '.$usersadded.' users added, '.$usersaddfailed.' users failed to add, '.$usersremoved.' users removed, '.$usersremovefailed.' users failed to remove.'.$this->nl;

		return array($usersadded, $usersremoved, $usersaddfailed, $usersremovefailed);
	}

	/**
	 * Gets a part of an array
	 * @param $groups to split
	 * @param $splitsize number of chunks to split the array into
	 * @param $part number of the chunk needed
	 * @return array subarray of $groups
	 */
	private function _splitGroups($groups, $splitsize, $part)
	{
		if ($splitsize < 1 || $part < 1 || $part > $splitsize)
		{
			echo "cannot get part $part of $splitsize parts";
			return $groups;
		}

		$totalsize = count($groups);

		if ($splitsize > $totalsize)
			$splitsize = $totalsize;

		$groupsize = floor($totalsize / $splitsize);

		if ($splitsize === $part)
		{
			$execgroupsize = $totalsize - ($splitsize - 1) * $groupsize;
		}
		else
			$execgroupsize = $groupsize;

		$startidx = $groupsize * ($part - 1);

		return array_slice($groups, $startidx, $execgroupsize);
	}

	/**
	 * Prints sync footer
	 * @param $syncname
	 * @param $results
	 * @param $starttime
	 * @param $endtime
	 * @param int $splitsize
	 * @param int $part
	 */
	private function _printSyncFooter($syncname, $results, $starttime, $endtime, $splitsize = 1, $part = 1)
	{
		$timedifference = date_diff($starttime, $endtime);

		echo $this->nl.str_repeat('-', 50);
		echo $this->nl.strtoupper($syncname).' SYNC FINISHED (PART '.$part.'/'.$splitsize.')';
		echo $this->nl.'ALTOGETHER: '.$results['groupsadded'].' groups added, '.$results['groupsaddfailed'].' groups failed to add, '.$results['usersadded'].' users added, ';
		echo $results['usersremoved'].' users removed, '.$results['usersaddfailed'].' users failed to add, '.$results['usersremovefailed'].' users failed to remove';
		echo $this->nl.'SYNC TOOK '.($timedifference->days == 0 ? '' : $timedifference->days.' days, ').$timedifference->h.' hours, '.$timedifference->i.' minutes, '.$timedifference->s.' seconds';
		echo $this->nl.'NEXTCLOUD '.strtoupper($syncname).' SYNC END';
		echo $this->nl.str_repeat('-', 50).$this->nl;
	}
}

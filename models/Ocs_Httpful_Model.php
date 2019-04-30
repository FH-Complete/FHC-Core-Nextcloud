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

class OCS_Httpful_Model extends CI_Model
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->NextcloudConfig = $this->config->item('FHC-Core-Nextcloud');
		$this->load->library('extensions/FHC-Core-Nextcloud/NextcloudClientLib');
	}

	/**
	 * Add a new group
	 * @param string $group Name of Group.
	 * @return boolean true if ok, false on error
	 */
	public function addGroup($group)
	{
		$response = $this->nextcloudclientlib->call('groups', 'POST', array('groupid' => $group));

		if ($this->nextcloudclientlib->isError())
		{
			return false;
		}

		if (isset($response->meta->statuscode) && $response->meta->statuscode == '100')
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Adds a User to an existing group
	 * @param string $group Name of the Group.
	 * @param string $user Name of the User to add.
	 * @return boolean true if ok, false on error
	 */
	public function addUserToGroup($group, $user)
	{
		$response = $this->nextcloudclientlib->call('users/'.$user.'/groups', 'POST', array('groupid' => $group));

		if ($this->nextcloudclientlib->isError())
		{
			return false;
		}

		if (isset($response->meta->statuscode))
		{
			switch ($response->meta->statuscode)
			{
				case "100":
					return true;
				case "102":
					echo PHP_EOL."group ".$group." does not exist";
					return false;
				default:
					return false;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * Search a user
	 * @param $user
	 * @return array of Users or false on error
	 */
	public function searchUser($user, $limit=0)
	{
		$response = $this->nextcloudclientlib->call('users?search='.$user, 'GET', array('limit' => $limit));

		if ($this->nextcloudclientlib->isError())
		{
			return false;
		}

		if ($response->meta->statuscode == '100')
		{
			$users = (array) $response->data->users->element;
			return $users;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Get the Members of a group
	 * @param string $group Name of the group.
	 * @return array of Users
	 */
	public function getGroupMember($group)
	{
		$response = $this->nextcloudclientlib->call('groups/'.$group);

		if ($this->nextcloudclientlib->isError())
		{
			return false;
		}

		if (isset($response->meta->statuscode) && $response->meta->statuscode == '100')
		{
			$users = (array) $response->data->users->element;
			return $users;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Get the Groups of the Nextcloud instance
	 * @return array of groups
	 */
	public function getGroups()
	{
		$response = $this->nextcloudclientlib->call('groups');

		if ($this->nextcloudclientlib->isError())
		{
			return false;
		}

		if (isset($response->meta->statuscode) && $response->meta->statuscode == '100')
		{
			$users = (array) $response->data->groups->element;
			return $users;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Get the Apps of the Nextcloud instance
	 * @return array of apps
	 */
	public function getApps()
	{
		$response = $this->nextcloudclientlib->call('apps');

		if ($this->nextcloudclientlib->isError())
		{
			return false;
		}

		if (isset($response->meta->statuscode) && $response->meta->statuscode == '100')
		{
			$apps = (array) $response->data->apps->element;
			return $apps;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Remove a User from an existing group
	 * @param string $group Name of the Group.
	 * @param string $user Name of the User to remove.
	 * @return boolean true if ok, false on error
	 */
	public function removeUserFromGroup($group, $user)
	{
		$response = $this->nextcloudclientlib->call('users/'.$user.'/groups', 'DELETE', array('groupid' => $group));

		if ($this->nextcloudclientlib->isError())
		{
			return false;
		}

		if (isset($response->meta->statuscode))
		{
			switch ($response->meta->statuscode)
			{
				case "100":
					return true;
				case "102":
					echo PHP_EOL."group ".$group." does not exist";
					return false;
				case "103":
					echo PHP_EOL."user ".$user." does not exist";
					return false;
				default:
					return false;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * Delete a group
	 * @param string $group Name of Group.
	 * @return boolean true if ok, false on error
	 */
	public function deleteGroup($group)
	{
		$response = $this->nextcloudclientlib->call('groups/'.$group, 'DELETE');

		if ($this->nextcloudclientlib->isError())
		{
			return false;
		}

		if (isset($response->meta->statuscode) && $response->meta->statuscode == '100')
		{
			return true;
		}
		else
		{
			return false;
		}
	}
}

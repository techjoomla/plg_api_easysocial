<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_api
 *
 * @copyright   Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://techjoomla.com
 * Work derived from the original RESTful API by Techjoomla (https://github.com/techjoomla/Joomla-REST-API)
 * and the com_api extension by Brian Edgerton (http://www.edgewebworks.com)
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/search.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/avatars.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/friends.php';
require_once JPATH_ROOT . '/components/com_finder/models/search.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';

/**
 * API class EasysocialApiResourceSearch
 *
 * @since  1.0
 */
class EasysocialApiResourceSearch extends ApiResource
{
	/**
	 * Method This function is use to fetch the User, Event and Group object based on the search keywords
	 *
	 * @return mixed
	 *
	 * @since 1.0
	 */
	public function get()
	{
		$this->getSearch();
	}

	/**
	 * Method POST does not used in this API
	 *
	 * @return mixed
	 *
	 * @since 1.0
	 */
	public function post()
	{
		$this->plugin->err_code = 405;
		$this->plugin->err_message = JText::_('PLG_API_EASYSOCIAL_USE_GET_METHOD_MESSAGE');
		$this->plugin->setResponse(null);
	}

	/**
	 * Method This function use for get object for friends, Events and Groups
	 *
	 * @return	mixed
	 *
	 * @since 1.0
	 */
	public function getSearch()
	{
		$app = JFactory::getApplication();
		$log_user = JFactory::getUser($this->plugin->get('user')->id);

		$search = $app->input->get('search', '', 'STRING');
		$type = $app->input->get('type', '', 'STRING');
		$limitstart = $app->input->get('limitstart', 0, 'INT');
		$limit = $app->input->get('limit', 10, 'INT');

		// Response object
		$res = new stdClass;
		$res->result = array();
		$res->empty_message = '';

		if ($limitstart)
		{
			$limit = $limit + $limitstart;
		}

		if (empty($search))
		{
			$res->empty_message = JText::_('PLG_API_EASYSOCIAL_EMPTY_SEARCHTEXT_MESSAGE');
			$this->plugin->setResponse($res);
		}

		if (empty($type))
		{
			$res->empty_message = JText::_('PLG_API_EASYSOCIAL_EMPTY_SEARCH_TYPE');
		}
		else
		{
			switch ($type)
			{
				case 'user':
							$res->result = $this->getFriendList($log_user, $search, $limitstart, $limit);

							if (empty($res->result))
							{
								// Message to show when the list is empty
								$res->empty_message = JText::_('PLG_API_EASYSOCIAL_USER_NOT_FOUND');
							}

					break;
				case 'event':
							$res->result = $this->getEventList($log_user, $search, $limitstart, $limit);

							if (empty($res->result->events))
							{
								// Message to show when the list is empty
								$res->empty_message = JText::_('PLG_API_EASYSOCIAL_SEARCH_EVENT_NOT_FOUND');
							}

					break;
				case 'page':
							$res->result = $this->getPageList($log_user, $search, $limitstart, $limit);

							if (empty($res->result))
							{
								// Message to show when the list is empty
								$res->empty_message = JText::_('PLG_API_EASYSOCIAL_PAGE_NOT_FOUND');
							}

					break;
				default :
							$res->result = $this->getGroupList($log_user, $search, $limitstart, $limit);

							if (empty($res->result))
							{
								// Message to show when the list is empty
								$res->empty_message = JText::_('PLG_API_EASYSOCIAL_GROUP_NOT_FOUND');
							}

					break;
			}
		}

		$this->plugin->setResponse($res);
	}

	/**
	 * Method Getting friends data
	 *
	 * @param   object   $log_user    user object
	 * @param   string   $search      search keyword
	 * @param   integer  $limitstart  limitstart
	 * @param   integer  $limit       limit
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function getFriendList($log_user, $search, $limitstart, $limit)
	{
		$mapp = new EasySocialApiMappingHelper;
		$userid = $log_user->id;
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('su.user_id')));
		$query->from($db->quoteName('#__social_users', 'su'));
		$query->join('LEFT', $db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('su.user_id') . ' = ' . $db->quoteName('u.id') . ')');

		if (!empty($search))
		{
			$query->where("(u.username LIKE '%" . $search . "%' ) OR ( u.name LIKE '%" . $search . "%')");
		}

		$query->order($db->quoteName('u.id') . 'ASC');

		$db->setQuery($query);
		$tdata = $db->loadObjectList();
		$block_model = FD::model('Blocks');
		$susers = array();

		foreach ($tdata as $ky => $val)
		{
			$block = $block_model->isBlocked($val->user_id, $userid);

			if (!$block)
			{
				$susers[]   = FD::user($val->user_id);
			}
		}

		// Manual pagination code
		$susers = array_slice($susers, $limitstart, $limit);
		$base_obj = $mapp->mapItem($susers, 'user', $log_user->id);

		return $this->createSearchObj($base_obj);
	}

	/**
	 * Method Fetch events data
	 *
	 * @param   object   $logUser     user object
	 * @param   string   $search      search keyword
	 * @param   integer  $limitstart  limitstart
	 * @param   integer  $limit       limit
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function getEventList($logUser, $search, $limitstart, $limit)
	{
		$mapp = new EasySocialApiMappingHelper;
		$result = new stdClass;

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('cl.id')));
		$query->from($db->quoteName('#__social_clusters', 'cl'));

		if (!empty($search))
		{
			$query->where("(cl.title LIKE '%" . $search . "%' )");
		}

		$query->where('cl.state = 1');
		$query->order($db->quoteName('cl.id') . 'ASC');
		$db->setQuery($query);
		$edata = $db->loadObjectList();
		$event = array();

		foreach ($edata as $ent)
		{
				$event[] = FD::event($ent->id);
		}

		// Manual pagination
		$event = array_slice($event, $limitstart, $limit);
		$result->events = $mapp->mapItem($event, 'event', $logUser->id);

		$catModel = FD::model('eventcategories');
		$result->categories = $catModel->getCategories();

		return $result;
	}

	// Fetch Groups data
	/**
	 * Method Format friends object into required object
	 *
	 * @param   object   $log_user    user object
	 * @param   string   $search      search keyword
	 * @param   integer  $limitstart  limitstart
	 * @param   integer  $limit       limit
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function getGroupList($log_user, $search, $limitstart, $limit)
	{
		/* Todo : Function depricated - Need to remove all search function and create one global function for all search type  */
		$mapp = new EasySocialApiMappingHelper;

		// $res = new stdClass;
		$group = array();

		$model = FD::model('Groups');
		$gdata = $model->search($search);

		foreach ($gdata as $grp)
		{
			$group_load = FD::group($grp->id);
			$is_inviteonly = $group_load->isInviteOnly();
			$is_member = $group_load->isMember($log_user->id);

			if ($is_inviteonly && !$is_member)
			{
				if ($group_load->creator_uid == $log_user->id)
				{
					$group[] = FD::group($grp->id);
				}
				else
				{
					continue;
				}
			}
			else
			{
				$group[] = FD::group($grp->id);
			}
		}

		// Manual pagination
		$group = array_slice($group, $limitstart, $limit);

		return $mapp->mapItem($group, 'group', $log_user->id);
	}

	// Fetch Pages data
	/**
	 * Method Format page object into required object
	 *
	 * @param   object   $log_user    user object
	 * @param   string   $search      search keyword
	 * @param   integer  $limitstart  limitstart
	 * @param   integer  $limit       limit
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function getPageList($log_user, $search, $limitstart, $limit)
	{
		$mapp = new EasySocialApiMappingHelper;
		$page = array();

		$page_model = FD::model('Pages');
		$pdata = $page_model->search($search);

		foreach ($pdata as $pageData)
		{
			$page_load = FD::page($pageData->id);
			$is_inviteonly = $page_load->isInviteOnly();
			$is_member = $page_load->isMember($log_user->id);

			if ($is_inviteonly && !$is_member)
			{
				if ($page_load->creator_uid == $log_user->id)
				{
					$page[] = FD::page($pageData->id);
				}
				else
				{
					continue;
				}
			}
			else
			{
				$page[] = FD::page($pageData->id);
			}
		}

		// Manual pagination
		$page = array_slice($page, $limitstart, $limit);

		return $mapp->mapItem($page, 'page', $log_user->id);
	}

	/**
	 * Method Format friends object into required object
	 *
	 * @param   object  $data  data
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function createSearchObj($data = null)
	{
		if ($data === null)
		{
			return $data;
		}

		$myarr = array();

		$user = JFactory::getUser($this->plugin->get('user')->id);
		$frnd_mod = new EasySocialModelFriends;
		$options = array();
		$options['state'] = SOCIAL_FRIENDS_STATE_PENDING;
		$options['isRequest'] = true;

		$req = $frnd_mod->getFriends($user->id, $options);

		if (!empty($req))
		{
			foreach ($req as $ky => $row)
			{
				$myarr[] = $row->id;
			}
		}

		foreach ($data as $k => $node)
		{
			if ($node->id != $user->id)
			{
				$node->mutual = $frnd_mod->getMutualFriendCount($user->id, $node->id);
				$node->isFriend = $frnd_mod->isFriends($user->id, $node->id);
				$node->approval_pending = $frnd_mod->isPendingFriends($user->id, $node->id);
				$node->isinitiator = (in_array($node->id, $myarr)) ? true : false;
			}
		}

		return $node;
	}
}

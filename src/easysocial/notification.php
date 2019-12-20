<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_api-plugin
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

require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/tables/friend.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/friends.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/uploadHelper.php';

/**
 * API class EasysocialApiResourceNotification
 *
 * @since  1.0
 */
class EasysocialApiResourceNotification extends ApiResource
{
	/**
	 * Method   description
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function get()
	{
		$app		=	JFactory::getApplication();
		$action		=	$app->input->get('action', '', 'STRING');
		$userid		=	$app->input->get('userid', 0, 'INT');
		

		switch($action)
		{
			case 'getunread': 		$this->getNotifications($userid);
									break;

			case 'cleanup' :		$this->markAsRead($userid);
									break;

			case 'unreadcount' :		$this->getNotificationCount($userid);
									break;

			default :			$this->plugin->setResponse($this->get_data());
									break;
		}
	}

	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function post()
	{
		$this->friendAddRemove();
	}

	/**
	 * Method forking respective function.
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function friendAddRemove()
	{
		$app = JFactory::getApplication();
		$flag = $app->input->get('flag', null, 'STRING');

		if ($flag == 'reject')
		{
			$result1 = $this->removeFriend();

			return $result1;
		}
		elseif ($flag == 'accept')
		{
			$result2 = $this->addfriend();

			return $result2;
		}
		elseif ($flag == 'cancelrequest')
		{
			$result3 = $this->requestCancel();

			return $result3;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method cancel friend request
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function requestCancel()
	{
		$app = JFactory::getApplication();

		// Getting target id and user id.
		$user = $app->input->get('user_id', 0, 'INT');
		$target = $app->input->get('target_id', 0, 'INT');

		// Loading friend model for getting id
		$friendmodel = FD::model('Friends');
		$state = SOCIAL_FRIENDS_STATE_FRIENDS;
		$status = $friendmodel->isFriends($user, $target, $state);

		$res = new stdClass;

		if (!$status)
		{
			ES::friends($target, $user)->cancel();
			$res->result->status = 1;
			$this->plugin->setResponse($res);
		}
		else
		{
			$res->result->status = 0;
		}

		$this->plugin->setResponse($res);
	}

	/**
	 * Method reject removeFriend
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function removeFriend()
	{
		$app = JFactory::getApplication();

		// Getting target id and user id.
		$user = $app->input->get('user_id', 0, 'INT');
		$target = $app->input->get('target_id', 0, 'INT');
		$friend = FD::table('Friend');

		// Loading friend model for getting id.
		$friendmodel = FD::model('Friends');
		$state = SOCIAL_FRIENDS_STATE_FRIENDS;
		$status = $friendmodel->isFriends($user, $target, $state);
		$addstate = $friend->loadByUser($user, $target);

		$res = new stdClass;

		if (!$addstate)
		{
			$res->result->message = JText::_('PLG_API_EASYSOCIAL_UNABLE_REJECT_FRIEND_REQ');
			$res->result->status = false;
			$this->plugin->setResponse($res);
		}

		if (!$status)
		{
			// Final call to reject friend request.
			ES::friends($target, $user)->reject();
		}
		else
		{
			$res->result->message = JText::_('PLG_API_EASYSOCIAL_UNABLE_REJECT_FRIEND_REQ');
			$res->result->status = false;
			$this->plugin->setResponse($res);
		}

		$res->result->message = JText::_('PLG_API_EASYSOCIAL_FRIEND_REQ_CANCEL');
		$res->result->status = true;
		$this->plugin->setResponse($res);
	}

	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function addfriend()
	{
		$app = JFactory::getApplication();
		$user = $app->input->get('user_id', 0, 'INT');
		$target = $app->input->get('target_id', 0, 'INT');
		$friend = FD::table('Friend');
		$state = SOCIAL_FRIENDS_STATE_FRIENDS;
		$friendmodel = FD::model('Friends');
		$status = $friendmodel->isFriends($user, $target, $state);
		$addstate = $friend->loadByUser($user, $target);
		$res = new stdClass;

		if (!$addstate)
		{
			$res->result->message = JText::_('PLG_API_EASYSOCIAL_UNBALE_ADD_FRIEND_REQ');
			$res->result->status = false;
			$this->plugin->setResponse($res);
		}

		if (!$status)
		{
			ES::friends($target, $user)->approve();
		}
		else
		{
			$res->result->message = JText::_('PLG_API_EASYSOCIAL_UNBALE_ADD_FRIEND_REQ');
			$res->result->status = false;
			$this->plugin->setResponse($res);
		}

		$res->result->message = JText::_('PLG_API_EASYSOCIAL_FRIEND_REQ_ACCEPT');
		$res->result->status = true;
		$this->plugin->setResponse($res);
	}

	/**
	 * Method common function for forking other functions
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function getData()
	{
		$app = JFactory::getApplication();
		$uid = $app->input->get('uid', 0, 'INT');
		$data = array();
		$data['messagecount'] = $this->getMessageCount($uid);
		$data['message'] = $this->getMessages($uid);
		$data['notificationcount'] = $this->getNotificationCount($uid);
		$data['notifications'] = $this->getNotifications($uid);
		$data['friendcount'] = $this->getFriendCount($uid);
		$data['friendreq'] = $this->getFriendRequest($uid);

		return $data;
	}

	/**
	 * Method getFriendCount
	 *
	 * @param   string  $uid  user id
	 *
	 * @return string
	 *
	 * @since 1.0
	 */
	public function getFriendRequest($uid)
	{
		$object = new EasySocialModelFriends;
		$result = $object->getPendingRequests($uid);

		return $result;
	}

	/**
	 * Method getFriendCount
	 *
	 * @param   string  $uid  user id
	 *
	 * @return string
	 *
	 * @since 1.0
	 */
	public function getFriendCount($uid)
	{
		$model = FD::model('Friends');
		$total = $model->getTotalRequests($uid);

		return $total;
	}

	/**
	 * Method getMessageCount
	 *
	 * @param   string  $uid  user id
	 *
	 * @return string
	 *
	 * @since 1.0
	 */
	public function getMessageCount($uid)
	{
		$model = FD::model('Conversations');
		$total = $model->getNewCount($uid, 'user');

		return $total;
	}

	/**
	 * Method getNotificationCount
	 *
	 * @param   string  $uid  user id
	 *
	 * @return string
	 *
	 * @since 1.0
	 */
	public function getNotificationCount($uid)
	{
		$options = array(
						'unread' => true,
						'target' => array('id' => $uid, 'type' => SOCIAL_TYPE_USER)
					);
		$model = FD::model('Notifications');
		$total = $model->getCount($options);

		$res->result['count'] = $total;
		$this->plugin->setResponse($res);
	}

	/**
	 * Method get_messages
	 *
	 * @param   string  $uid  user id
	 *
	 * @return string
	 *
	 * @since 1.0
	 */
	public function getMessages($uid)
	{
			$maxlimit = 0;

			// Get the conversations model
			$model = FD::model('Conversations');

			// We want to sort items by latest first
			$options = array('sorting' => 'lastreplied', 'maxlimit' => $maxlimit);

			// Get conversation items.
			$conversations = $model->getConversations($uid, $options);

			return $conversations;
	}

	/**
	 * Method getNotifications
	 *
	 * @param   string  $uid  user id
	 *
	 * @return string
	 *
	 * @since 1.0
	 */
	public function getNotifications($uid)
	{
		jimport('joomla.filesystem.file');

		$res = new stdClass;
		$res->result = [];

		$model	=	FD::notification();

		$options = array(
		'unread' => true,
		'target_id' => $userid,
		'target_type' => SOCIAL_TYPE_USER
		);

		$items = $model->getItems($options);
		
		foreach ($items as $ky => $item)
		{
			if (boolval(JFile::exists($item->image)))
			{
				$image = JURI::root() . $item->image;

				if (!JFile::exists($image))
				{
					$item->image = "";
				}
			}
		}

		if($items)
		{
			$res->result = $items;

		}
		else
		{
			
			$res->result = [];
		 }

		$this->plugin->setResponse($res);
	}


	/**
	 * Method cleanup notification
	 *
	 * @param   string  $uid  user id
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function markAsRead($userid)
	{
		
		$res = new stdClass;
		$res->result = [];

		$model = ES::model('Notifications');
		$result = $model->setAllState(SOCIAL_NOTIFICATION_STATE_READ);
		
		if (!$result) {
			$res->result->message	=	JText::_('COM_EASYSOCIAL_NOTIFICATIONS_FAILED_TO_MARK_AS_READ');
		}
		else{
			$res->message = JText::_('PLG_API_EASYSOCIAL__NOTIFICATIONS_MARKED_AS_READ');
		}
		
		$this->plugin->setResponse($res);
		
	}
}

<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_api-plugins
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

require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/groups.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/covers.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/albums.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';

/**
 * API class EasysocialApiResourceMessage
 *
 * @since  1.0
 */

class EasysocialApiResourceMessage extends ApiResource
{
	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function get()
	{
		$this->getConversations();
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
		$this->newMessage();
	}

	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	private function newMessage()
	{
		$app = JFactory::getApplication();
		$recipients = $app->input->get('recipients', null, 'ARRAY');
		$msg = $app->input->get('message', null, 'RAW');

		// $target_usr = $app->input->get('target_user', 0, 'INT');
		$conversion_id = $app->input->get('conversion_id', 0, 'INT');
		$log_usr = $this->plugin->get('user')->id;
		$canCreate = ES::user();

		// Normalize CRLF (\r\n) to just LF (\n)
		$msg = str_ireplace("\r\n", "\n", $msg);

		$res = new stdclass;

		// Check if the user really has access to create groups
		if (! $canCreate->getAccess()->allowed('conversations.create') && ! $canCreate->isSiteAdmin())
		{
			ApiError::raiseError(403, JText::_('COM_EASYSOCIAL_CONVERSATIONS_ERROR_NOT_ALLOWED'));
		}

		if (count($recipients) < 1)
		{
			ApiError::raiseError(403, JText::_('PLG_API_EASYSOCIAL_EMPTY_MESSAGE_MESSAGE'));
		}

		// Message should not be empty.
		if (empty($msg))
		{
			ApiError::raiseError(403, JText::_('PLG_API_EASYSOCIAL_EMPTY_MESSAGE_MESSAGE'));
		}

		if ($conversion_id == 0)
		{
			$state = $this->createConversion($recipients, $log_usr, $msg);
		}

		if ($conversion_id)
		{
			$conversation = ES::conversation($conversion_id);
			$post_data = array();
			$post_data['uid'] = $recipients;
			$post_data['message'] = $msg;
			$conversation->bind($post_data);
			$state = $conversation->save();
		}

		if ($state)
		{
			$res->result->status = 1;
			$res->result->message = JText::_('PLG_API_EASYSOCIAL_MESSAGE_SENT_MESSAGE');
		}
		else
		{
			// Create result obj
			ApiError::raiseError(400, JText::_('PLG_API_EASYSOCIAL_UNABLE_SEND_MESSAGE'));
		}

		$this->plugin->setResponse($res);
	}

	/**
	 * Method createConversion
	 *
	 * @param   array   $recipients  array of receipients
	 * @param   string  $msg         message
	 *
	 * @return mixed
	 *
	 * @since 1.0
	 */
	private function createConversion($recipients, $msg)
	{
		$conversation = ES::conversation();
		$allowed = $conversation->canCreate();

		if (!$allowed)
		{
			return false;
		}

		$post_data = array();
		$post_data['uid'] = $recipients;
		$post_data['message'] = $msg;
		$conversation->bind($post_data);
		$state = $conversation->save();

		return $state;
	}

	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function delete()
	{
		$app = JFactory::getApplication();
		$conversion_id = $app->input->get('conversation_id', 0, 'INT');

		$res = new stdclass;

		if (!$conversion_id)
		{
			ApiError::raiseError(403, JText::_('PLG_API_EASYSOCIAL_INVALID_CONVERSATION_MESSAGE'));
		}

		// Try to delete the group
		$conv_model = ES::model('Conversations');
		$res->result->status = $conv_model->delete($conversion_id, $this->plugin->get('user')->id);
		$res->result->message = JText::_('PLG_API_EASYSOCIAL_CONVERSATION_DELETED_MESSAGE');

		$this->plugin->setResponse($res);
	}

	/**
	 * Method function use for get friends data
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */

	private function getConversations()
	{
		// Init variable
		$app = JFactory::getApplication();
		$logUser = JFactory::getUser($this->plugin->get('user')->id);
		$conversationId = $app->input->get('conversation_id', 0, 'INT');
		$limitstart = $app->input->get('limitstart', 0, 'INT');
		$limit = $app->input->get('limit', 50, 'INT');
		$maxlimit = $app->input->get('maxlimit', 100, 'INT');
		$filter = $app->input->get('filter', null, 'STRING');
		$mapp = new EasySocialApiMappingHelper;
		$res = new stdclass;
		$res->result = array();
		$res->empty_message = '';
		$convModel = ES::model('Conversations');

		// Set the startlimit
		$convModel->setState('limitstart', $limitstart);
		$options		=	array();
		$options['limit'] = $limit;

		if ($conversationId)
		{
			$data = array();
			$data['participant']	=	$this->getParticipantUsers($conversationId);
			$msg_data				=	$convModel->getMessages($conversationId, $logUser->id, $options);
			$res->result			=	$mapp->mapItem($msg_data, 'message', $logUser->id);
		}
		else
		{
			// Sort items by latest first
			$options = array('sorting' => 'lastreplied', 'maxlimit' => $maxlimit);

			if ($filter)
			{
				$options['filter'] = $filter;
			}

			$conversion = $convModel->getConversations($logUser->id, $options);

			/*
			 * $conversation->conversation->isparticipant = $row->isparticipant;
			 * $conversation = ES::conversation($row->id);
			 * $msg = $conversation->getMessages();
			*/

			if (count($conversion) > 0)
			{
				$res->result = $mapp->mapItem($conversion, 'conversion', $logUser->id);
				$res->result = array_slice($res->result, $limitstart, $limit);
			}
			else
			{
				$res->empty_message = JText::_('COM_EASYSOCIAL_CONVERSATION_EMPTY_LIST');
			}
		}

		$this->plugin->setResponse($res);
	}

	/**
	 * Method getParticipantUsers
	 *
	 * @param   int  $con_id  Conversation ID
	 *
	 * @return mixed
	 *
	 * @since 1.0
	 */
	private function getParticipantUsers($con_id)
	{
		$conv_model = ES::model('Conversations');
		$mapp = new EasySocialApiMappingHelper;
		$participant_usrs = $conv_model->getParticipants($con_id);
		$con_usrs = array();

		foreach ($participant_usrs as $ky => $usrs)
		{
			if ($usrs->id && ($this->plugin->get('user')->id != $usrs->id))
			{
				$con_usrs[] = $mapp->createUserObj($usrs->id);
			}

			return $con_usrs;
		}
	}
}

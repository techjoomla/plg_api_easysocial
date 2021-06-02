<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_api-plugins
 *
 * @copyright   Copyright (C) 2009-2021 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://techjoomla.com
 * Work derived from the original RESTful API by Techjoomla (https://github.com/techjoomla/Joomla-REST-API)
 * and the com_api extension by Brian Edgerton (http://www.edgewebworks.com)
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/uploadHelper.php';
/**
 * API class EasysocialApiResourceGroup
 *
 * @since  1.0
 */
class EasysocialApiResourcePage extends ApiResource
{
	/**
	 * Method description
	 *
	 * @return	object|boolean	in success object will return, in failure boolean
	 *
	 * @since 1.0
	 */
	public function get()
	{
		// Init variable
		$app      = Factory::getApplication();
		$log_user = Factory::getUser($this->plugin->get('user')->id);
		$pageId   = $app->input->get('id', 0, 'INT');
		$page[]   = FD::page($pageId);

		// Ensure that the id provided is valid
		if (empty($page[0]) || !$page[0]->id)
		{
			ApiError::raiseError(400, Text::_('PLG_API_EASYSOCIAL_PAGE_NOT_FOUND'));
		}

		$res                = new stdclass;
		$res->result        = array();
		$res->empty_message = '';

		$mapp        = new EasySocialApiMappingHelper;
		$pageObject  = $mapp->mapItem($page, SOCIAL_TYPE_PAGE, $log_user->id, SOCIAL_TYPE_PAGE);
		$fieldsModel = FD::model('Fields');

		foreach ($pageObject[0]->more_info as $key => $value)
		{
			$updatedCustomFieldValue = $fieldsModel->getCustomFieldsValue($value->field_id ,$pageId ,SOCIAL_TYPE_PAGE);
			$pageObject[0]->more_info[$key]->field_value = $updatedCustomFieldValue;
		}

		$res->result = $pageObject;
		$this->plugin->setResponse($res);
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
		$this->plugin->setResponse(Text::_('PLG_API_EASYSOCIAL_UNSUPPORTED_POST_METHOD_MESSAGE'));
	}

	/**
	 * Method description
	 *
	 * @return	object|boolean	in success object will return, in failure boolean
	 *
	 * @since 1.0
	 */
	public function delete()
	{
		$app     = Factory::getApplication();
		$page_id = $app->input->get('id', 0, 'INT');
		$valid   = 1;
		$page    = FD::page($page_id);

		// Call groups model to get page owner
		$pagesModel = FD::model('groups');
		$res		= new stdclass;

		if (!$page->id || !$page_id)
		{
			$res->result->status  = 0;
			$res->result->message = Text::_('PLG_API_EASYSOCIAL_INVALID_PAGE_MESSAGE');
			$valid = 0;
		}

		// Only allow super admins to delete pages
		$my	=	FD::user($this->plugin->get('user')->id);

		if (!$my->isSiteAdmin() && !$pagesModel->isOwner($my->id, $page_id))
		{
			$res->result->status  = 0;
			$res->result->message = Text::_('PLG_API_EASYSOCIAL_PAGE_ACCESS_DENIED_MESSAGE');
			$valid				  =	0;
		}

		if ($valid)
		{
			// Try to delete the page
			$page->delete();
			$res->result->status  = 1;
			$res->result->message = Text::_('PLG_API_EASYSOCIAL_PAGE_DELETED_MESSAGE');
		}

		$this->plugin->setResponse($res);
	}
}

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

require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';

/**
 * API class EasysocialApiResourceLeaderboard
 *
 * @since  1.0
 */
class EasysocialApiResourceLeaderboard extends ApiResource
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
		$this->getLeaderboard();
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
		$this->plugin->err_code = 405;
		$this->plugin->err_message = JText::_('PLG_API_EASYSOCIAL_USE_GET_METHOD_MESSAGE');
		$this->plugin->setResponse(null);
	}

	/**
	 * Method Get leaderboards.
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function getLeaderboard()
	{
		$app = JFactory::getApplication();
		$limitstart = $app->input->get('limitstart', 0, 'INT');
		$limit = $app->input->get('limit', 10, 'INT');
		$mapp = new EasySocialApiMappingHelper;
		$model = FD::model('Leaderboard');
		$excludeAdmin = true;
		$options = array('ordering' => 'points', 'excludeAdmin' => $excludeAdmin,'state' => 1, 'limit' => $limit);
		$users = $model->getLadder($options, false);

		// Response object
		$res = new stdClass;
		$res->result = array();
		$res->empty_message = '';

		if (empty($users))
		{
			$res->empty_message = JText::_('PLG_API_EASYSOCIAL_NO_LEADERS');

			$this->plugin->setResponse($res);
		}

		$res->result = $mapp->mapItem($users, 'user');

		$this->plugin->setResponse($res->result);
	}
}

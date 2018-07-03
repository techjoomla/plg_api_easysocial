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

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/videos.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE . '/components/com_easysocial/controllers/videos.php';

/**
 * API class EasysocialApiResourceVideos_link
 *
 * @since  1.0
 */
class EasysocialApiResourceVideos_Link extends ApiResource
{
	/**
	 * Function for retrieve post video
	 * 	 
	 * @return  JSON
	 */
	public function post()
	{
		$input = JFactory::getApplication()->input;
		$post = array();
		$post['source'] = $input->get('source', 'link', 'STRING');
		$logUser = $this->plugin->get('user')->id;

		// Check title
		if ($post['title'])
		{
			ApiError::raiseError(403, JText::_('PLG_API_EASYSOCIAL_VIDEO_TITLE'));
		}

		// Determine if this user has the permissions to create video.
		$access 	= ES::access();
		$allowed	= $access->get('videos.create');

		if (!$allowed)
		{
			ApiError::raiseError(403, JText::_('PLG_API_EASYSOCIAL_VIDEO_NOT_ALLOW_MESSAGE'));
		}

		$canCreate = ES::user($logUser);
		$total = $canCreate->getTotalVideos($post);

		if ($access->exceeded('videos.total', $total) || $access->exceeded('videos.daily', $total))
		{
			ApiError::raiseError(403, JText::_('PLG_API_EASYSOCIAL_VIDEO_CREATE_EXCEEDED_LIMIT'));
		}

		if ($post['source'] == 'link')
		{
			$this->linkVideo();
		}
		else
		{
			$this->uploadVideo();
		}
	}

	/**	  
	 * Function for retrieve save video
	 * 	 
	 * @return  JSON	 
	 */
	private function linkVideo()
	{
		$input = JFactory::getApplication()->input;
		$postData = $input->post->getArray();
		$postData['link'] = $input->get('path', '', 'STRING');

		$video = ES::video();
		$res = new stdClass;

		// Check link
		if (empty($postData['link']))
		{
			ApiError::raiseError(403, JText::_('PLG_API_EASYSOCIAL_VIDEO_LNK_INVALID_URL'));
		}

		if ($postData['link'])
		{
			$rx = '~
			^(?:https?://)?              # Optional protocol
			(?:www\.)?                  # Optional subdomain
			(?:youtube\.com|youtu\.be|vimeo\.com)  # Mandatory domain name
			/watch\?v=([^&]+)           # URI with video id as capture group 1
			~x';
		}

		$isNew = $video->isNew();

		// Set the current user id only if this is a new video, otherwise whenever the video is edited,
		// the owner get's modified as well.
		if ($isNew)
		{
			$video->table->user_id = $video->my->id;
		}

		// Video links
		if ($video->table->isLink())
		{
			$video->table->path = $postData['link'];

			// Grab the video data
			$crawler = ES::crawler();
			$crawler->crawl($video->table->path);

			$scrape = (object) $crawler->getData();

			// Set the video params with the scraped data
			$video->table->params = json_encode($scrape);

			// Set the video's duration
			$video->table->duration = @$scrape->oembed->duration;
			$video->processLinkVideo();

			// Save the video
			$state = $video->save($postData, $video);

			if ($state)
			{
				$video->table->store();
				$video->table->hit();
				$this->createVideoStream($postData, $video);
				$res->result->message = JText::_('PLG_API_EASYSOCIAL_VIDEO_LNK_UPLOAD_SUCCESS');
			}
			else
			{
				ApiError::raiseError(400, JText::_('PLG_API_EASYSOCIAL_VIDEO_UNABLE_UPLOAD'));
			}
		}

		$this->plugin->setResponse($res);
	}

	/**	  
	 * Function for retrieve save video
	 * 	 
	 * @return  JSON	 
	 */
	private function uploadVideo()
	{
		$input = JFactory::getApplication()->input;
		$logUser = $this->plugin->get('user')->id;

		$postData = $input->post->getArray();
		$postData['uid'] = $input->get('uid', $logUser, 'INT');

		$res = new stdClass;

		$file = $input->files->get('video');

		if (empty($file))
		{
			ApiError::raiseError(403, JText::_('PLG_API_EASYSOCIAL_VIDEO_SELECT'));
		}

		$uid = $input->get('uid', $log_user, 'INT') ? $postData['uid'] : $logUser;
		$type = $input->get('type', SOCIAL_TYPE_USER, 'STRING');

		$video = ES::video($uid, $type);
		$isNew = $video->isNew();

		if ($isNew)
		{
			$video->table->user_id = $video->my->id;
		}

		$state = $video->save($postData, $file);

		if ($state)
		{
			$video->process();
			$video->table->state = SOCIAL_VIDEO_PUBLISHED;
			$video->table->store();
			$this->createVideoStream($postData, $video);
			$res->result->message = JText::_('PLG_API_EASYSOCIAL_VIDEO_LNK_UPLOAD_SUCCESS');
		}
		else
		{
			ApiError::raiseError(400, JText::_('PLG_API_EASYSOCIAL_VIDEO_UNABLE_UPLOAD'));
		}

		$this->plugin->setResponse($res);
	}

	/**
	 * Function for post video on stream page
	 * 
	 * @param   Object  $post   The video post data
	 * @param   Object  $video  The video data
	 * 
	 * @return  void
	 */
	private function createVideoStream($post, $video)
	{
		// Comman code for video upload and link display on stream page

		// Bind the video location
		if (isset($post['location']) && $post['location'] && isset($post['latitude']) && $post['latitude'] && isset($post['longitude'])
			&& $post['longitude'])
		{
			// Create a location for this video
			$location = ES::table('Location');

			$location->uid = $video->table->id;
			$location->type = SOCIAL_TYPE_VIDEO;
			$location->user_id = $video->my->id;
			$location->address = $video['location'];
			$location->latitude = $video['latitude'];
			$location->longitude = $video['longitude'];
			$location->store();
		}

		$privacyData = 'public';

		if (isset($post['privacy']))
		{
			$privacyData = new stdClass;
			$privacyData->rule = 'videos.view';
			$privacyData->value = $post['privacy'];
			$privacyData->custom = $post['privacyCustom'];

			$video->insertPrivacy($privacyData);
		}

		$video->createStream('create', $privacyData);
	}
}

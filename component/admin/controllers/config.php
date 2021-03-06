<?php
/**
 * @package     Redcore.Backend
 * @subpackage  Controllers
 *
 * @copyright   Copyright (C) 2012 - 2013 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later, see LICENSE.
 */

defined('_JEXEC') or die;

/**
 * Config Controller
 *
 * @package     Redcore.Backend
 * @subpackage  Controllers
 * @since       1.0
 */
class RedcoreControllerConfig extends RControllerForm
{
	/**
	 * Method to install Content Element.
	 *
	 * @return  boolean  True if successful, false otherwise.
	 */
	public function installContentElement()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		$app   = JFactory::getApplication();

		$option = $app->input->getString('component');
		$xmlFile = $app->input->getString('contentElement');

		if ($xmlFile == 'all')
		{
			RTranslationTable::batchContentElements($option, 'install');
		}
		else
		{
			RTranslationTable::installContentElement($option, $xmlFile);
		}

		parent::edit();
	}

	/**
	 * Method to uninstall Content Element.
	 *
	 * @return  boolean  True if successful, false otherwise.
	 */
	public function uninstallContentElement()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		$app   = JFactory::getApplication();

		$option = $app->input->getString('component');
		$xmlFile = $app->input->getString('contentElement');

		if ($xmlFile == 'all')
		{
			RTranslationTable::batchContentElements($option, 'uninstall');
		}
		else
		{
			RTranslationTable::uninstallContentElement($option, $xmlFile);
		}

		parent::edit();
	}

	/**
	 * Method to purge Content Element Table.
	 *
	 * @return  boolean  True if successful, false otherwise.
	 */
	public function purgeContentElement()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		$app   = JFactory::getApplication();

		$option = $app->input->getString('component');
		$xmlFile = $app->input->getString('contentElement');

		if ($xmlFile == 'all')
		{
			RTranslationTable::batchContentElements($option, 'purge');
		}
		else
		{
			RTranslationTable::purgeContentElement($option, $xmlFile);
		}

		parent::edit();
	}

	/**
	 * Method to delete Content Element file.
	 *
	 * @return  boolean  True if successful, false otherwise.
	 */
	public function deleteContentElement()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		$app   = JFactory::getApplication();

		$option = $app->input->getString('component');
		$xmlFile = $app->input->getString('contentElement');

		if ($xmlFile == 'all')
		{
			RTranslationTable::batchContentElements($option, 'delete');
		}
		else
		{
			RTranslationTable::deleteContentElement($option, $xmlFile);
		}

		parent::edit();
	}

	/**
	 * Method to upload Content Element file.
	 *
	 * @return  boolean  True if successful, false otherwise.
	 */
	public function uploadContentElement()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		$app   = JFactory::getApplication();
		$option = $app->input->getString('component');
		$files  = $app->input->files->get('redcoreContentElement', array(), 'array');

		if (!empty($files))
		{
			$uploadedFiles = RTranslationTable::uploadContentElement($option, $files);

			if (!empty($uploadedFiles))
			{
				$app->enqueueMessage(JText::_('COM_REDCORE_CONFIG_TRANSLATIONS_UPLOAD_SUCCESS'));
			}
		}
		else
		{
			JFactory::getApplication()->enqueueMessage(JText::_('COM_REDCORE_CONFIG_TRANSLATIONS_UPLOAD_FILE_NOT_FOUND'), 'warning');
		}

		parent::edit();
	}

	/**
	 * Method to save a record.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 */
	public function save($key = null, $urlVar = null)
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$app   = JFactory::getApplication();
		$lang  = JFactory::getLanguage();
		$model = $this->getModel();
		$table = $model->getTable();
		$data  = $this->input->post->get('jform', array(), 'array');
		$checkin = property_exists($table, 'checked_out');
		$context = "$this->option.edit.$this->context";
		$task = $this->getTask();
		$id = $this->input->getInt('id');

		// Determine the name of the primary key for the data.
		if (empty($key))
		{
			$key = $table->getKeyName();
		}

		// To avoid data collisions the urlVar may be different from the primary key.
		if (empty($urlVar))
		{
			$urlVar = $key;
		}

		$recordId = $this->input->getInt($urlVar);

		if (!$this->checkEditId($context, $recordId))
		{
			// Somehow the person just went to the form and tried to save it. We don't allow that.
			$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $recordId));
			$this->setMessage($this->getError(), 'error');

			// Redirect to the list screen
			$this->setRedirect(
				$this->getRedirectToListRoute($this->getRedirectToListAppend())
			);

			return false;
		}

		// Populate the row id from the session.
		$data[$key] = $recordId;

		// The save2copy task needs to be handled slightly differently.
		if ($task == 'save2copy')
		{
			// Check-in the original row.
			if ($checkin && $model->checkin($data[$key]) === false)
			{
				// Check-in failed. Go back to the item and display a notice.
				$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError()));
				$this->setMessage($this->getError(), 'error');

				// Redirect back to the edit screen.
				$this->setRedirect(
					$this->getRedirectToItemRoute($this->getRedirectToItemAppend($recordId, $urlVar))
				);

				return false;
			}

			// Reset the ID and then treat the request as for Apply.
			$data[$key] = 0;
			$task = 'apply';
		}

		// Access check.
		if (!$this->allowSave($data, $key))
		{
			$this->setError(JText::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'));
			$this->setMessage($this->getError(), 'error');

			// Redirect to the list screen
			$this->setRedirect(
				$this->getRedirectToListRoute($this->getRedirectToListAppend())
			);

			return false;
		}

		// Validate the posted data.
		// Sometimes the form needs some posted data, such as for plugins and modules.
		$form = $model->getForm($data, false);

		if (!$form)
		{
			$app->enqueueMessage($model->getError(), 'error');

			return false;
		}

		// Test whether the data is valid.
		$validData = $model->validate($form, $data);

		// Check for validation errors.
		if ($validData === false)
		{
			// Get the validation messages.
			$errors = $model->getErrors();

			// Push up to three validation messages out to the user.
			for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
			{
				if ($errors[$i] instanceof Exception)
				{
					$app->enqueueMessage($errors[$i]->getMessage(), 'warning');
				}
				else
				{
					$app->enqueueMessage($errors[$i], 'warning');
				}
			}

			// Save the data in the session.
			$app->setUserState($context . '.data', $data);

			// Redirect back to the edit screen.
			$this->setRedirect(
				$this->getRedirectToItemRoute($this->getRedirectToItemAppend($recordId, $urlVar))
			);

			return false;
		}

		$validData = array(
			'params' => $validData,
			'id' => $id
		);

		if (!isset($validData['tags']))
		{
			$validData['tags'] = null;
		}

		// Attempt to save the data.
		if (!$model->save($validData))
		{
			// Save the data in the session.
			$app->setUserState($context . '.data', $validData);

			// Redirect back to the edit screen.
			$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $model->getError()));
			$this->setMessage($this->getError(), 'error');

			// Redirect back to the edit screen.
			$this->setRedirect(
				$this->getRedirectToItemRoute($this->getRedirectToItemAppend($recordId, $urlVar))
			);

			return false;
		}

		// Save succeeded, so check-in the record.
		if ($checkin && $model->checkin($validData[$key]) === false)
		{
			// Save the data in the session.
			$app->setUserState($context . '.data', $validData);

			// Check-in failed, so go back to the record and display a notice.
			$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError()));
			$this->setMessage($this->getError(), 'error');

			// Redirect back to the edit screen.
			$this->setRedirect(
				$this->getRedirectToItemRoute($this->getRedirectToItemAppend($recordId, $urlVar))
			);

			return false;
		}

		$this->setMessage(
			JText::_(
				($lang->hasKey($this->text_prefix . ($recordId == 0 && $app->isSite() ? '_SUBMIT' : '') . '_SAVE_SUCCESS')
					? $this->text_prefix
					: 'JLIB_APPLICATION') . ($recordId == 0 && $app->isSite() ? '_SUBMIT' : '') . '_SAVE_SUCCESS'
			)
		);

		// Redirect the user and adjust session state based on the chosen task.
		switch ($task)
		{
			case 'apply':
				// Set the record data in the session.
				$recordId = $model->getState($this->context . '.id');
				$this->holdEditId($context, $recordId);
				$app->setUserState($context . '.data', null);
				$model->checkout($recordId);

				// Redirect back to the edit screen.
				$this->setRedirect(
					$this->getRedirectToItemRoute($this->getRedirectToItemAppend($recordId, $urlVar))
				);
				break;

			case 'save2new':
				// Clear the record id and data from the session.
				$this->releaseEditId($context, $recordId);
				$app->setUserState($context . '.data', null);

				// Redirect back to the edit screen.
				$this->setRedirect(
					$this->getRedirectToItemRoute($this->getRedirectToItemAppend(null, $urlVar))
				);
				break;

			default:
				// Clear the record id and data from the session.
				$this->releaseEditId($context, $recordId);
				$app->setUserState($context . '.data', null);

				// Set redirect
				$this->setRedirect(
					$this->getRedirectToListRoute($this->getRedirectToListAppend())
				);
				break;
		}

		// Invoke the postSave method to allow for the child class to access the model.
		$this->postSaveHook($model, $validData);

		return true;
	}

	/**
	 * Gets the URL arguments to append to an item redirect.
	 *
	 * @param   integer  $recordId  The primary key id for the item.
	 * @param   string   $urlVar    The name of the URL variable for the id.
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 */
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		$append = parent::getRedirectToItemAppend($recordId, $urlVar);

		$component = $this->input->get('component');
		$tab = $this->input->get('tab');

		if ($component)
		{
			$append .= '&component=' . $component;
		}

		if ($tab)
		{
			$append .= '&tab=' . $tab;
		}

		return $append;
	}

	/**
	 * Gets the URL arguments to append to a list redirect.
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 */
	protected function getRedirectToListAppend()
	{
		$tmpl = JFactory::getApplication()->input->get('tmpl');
		$append = '';

		// Setup redirect info.
		if ($tmpl)
		{
			$append .= '&tmpl=' . $tmpl;
		}

		return $append;
	}
}

<?php

	class mailingActions extends TBGAction
	{

		/**
		 * Forgotten password logic (AJAX call)
		 *
		 * @param TBGRequest $request
		 */
		public function runForgot(TBGRequest $request)
		{
			$i18n = TBGContext::getI18n();

			try
			{
				$username = str_replace('%2E', '.', $request['forgot_password_username']);
				if (!empty($username))
				{
					if (($user = TBGUser::getByUsername($username)) instanceof TBGUser)
					{
						if($user->isActivated() && $user->isEnabled() && !$user->isDeleted())
						{
							if ($user->getEmail())
							{
								TBGMailing::getModule()->sendForgottenPasswordEmail($user);
								return $this->renderJSON(array('message' => $i18n->__('Please use the link in the email you received')));
							}
							else
							{
								throw new Exception($i18n->__('Cannot find an email address for this user'));
							}
						}
						else
						{
							throw new Exception($i18n->__('Forbidden for this username, please contact your administrator'));
						}
					}
					else
					{
						throw new Exception($i18n->__('This username does not exist'));
					}
				}
				else
				{
					throw new Exception($i18n->__('Please enter an username'));
				}
			}
			catch (Exception $e)
			{
				$this->getResponse()->setHttpStatus(400);
				return $this->renderJSON(array('error' => $e->getMessage()));
			}
		}

		/**
		 * Send a test email
		 *
		 * @param TBGRequest $request
		 */
		public function runTestEmail(TBGRequest $request)
		{
			if ($email_to = $request['test_email_to'])
			{
				try
				{
					if (TBGMailing::getModule()->sendTestEmail($email_to))
					{
						TBGContext::setMessage('module_message', TBGContext::getI18n()->__('The email was successfully accepted for delivery'));
					}
					else
					{
						TBGContext::setMessage('module_error', TBGContext::getI18n()->__('The email was not sent'));
						TBGContext::setMessage('module_error_details', TBGLogging::getMessagesForCategory('mailing', TBGLogging::LEVEL_NOTICE));
					}
				}
				catch (Exception $e)
				{
					TBGContext::setMessage('module_error', TBGContext::getI18n()->__('The email was not sent'));
					TBGContext::setMessage('module_error_details', $e->getMessage());
				}
			}
			else
			{
				TBGContext::setMessage('module_error', TBGContext::getI18n()->__('Please specify an email address'));
			}
			$this->forward(TBGContext::getRouting()->generate('configure_module', array('config_module' => 'mailing')));
		}
		
		public function runSaveIncomingAccount(TBGRequest $request)
		{
			$project = null;
			if ($project_key = $request['project_key'])
			{
				try
				{
					$project = TBGProject::getByKey($project_key);
				}
				catch (Exception $e) {}
			}
			if ($project instanceof TBGProject)
			{
				try
				{
					$account_id = $request['account_id'];
					$account = ($account_id) ? new TBGIncomingEmailAccount($account_id) : new TBGIncomingEmailAccount();
					$account->setIssuetype((integer) $request['issuetype']);
					$account->setProject($project);
					$account->setPort((integer) $request['port']);
					$account->setName($request['name']);
					$account->setFoldername($request['folder']);
					$account->setKeepEmails($request['keepemail']);
					$account->setServer($request['servername']);
					$account->setUsername($request['username']);
					$account->setPassword($request['password']);
					$account->setSSL((boolean) $request['ssl']);
					$account->setIgnoreCertificateValidation((boolean) $request['ignore_certificate_validation']);
					$account->setUsePlaintextAuthentication((boolean) $request['plaintext_authentication']);
					$account->setServerType((integer) $request['account_type']);
					$account->save();

					if (!$account_id)
					{
						return $this->renderTemplate('mailing/incomingemailaccount', array('project' => $project, 'account' => $account));
					}
					else
					{
						return $this->renderJSON(array('name' => $account->getName()));
					}
				}
				catch (Exception $e)
				{
					$this->getResponse()->setHttpStatus(400);
					return $this->renderJSON(array('error' => $this->getI18n()->__('This is not a valid mailing account')));
				}
			}
			else
			{
				$this->getResponse()->setHttpStatus(400);
				return $this->renderJSON(array('error' => $this->getI18n()->__('This is not a valid project')));
			}
		}
		
		public function runCheckIncomingAccount(TBGRequest $request)
		{
			TBGContext::loadLibrary('common');
			if ($account_id = $request['account_id'])
			{
				try
				{
					$account = new TBGIncomingEmailAccount($account_id);
					try
					{
                        if (!function_exists('imap_open'))
                        {
                            throw new Exception($this->getI18n()->__('The php imap extension is not installed'));
                        }
						TBGContext::getModule('mailing')->processIncomingEmailAccount($account);
					}
					catch (Exception $e)
					{
						$this->getResponse()->setHttpStatus(400);
						return $this->renderJSON(array('error' => $e->getMessage()));
					}

					return $this->renderJSON(array('account_id' => $account->getID(), 'time' => tbg_formatTime($account->getTimeLastFetched(), 6), 'count' => $account->getNumberOfEmailsLastFetched()));
				}
				catch (Exception $e)
				{
					$this->getResponse()->setHttpStatus(400);
					return $this->renderJSON(array('error' => $this->getI18n()->__('This is not a valid mailing account')));
				}
			}
			else
			{
				$this->getResponse()->setHttpStatus(400);
				return $this->renderJSON(array('error' => $this->getI18n()->__('This is not a valid mailing account')));
			}
		}
		
		public function runDeleteIncomingAccount(TBGRequest $request)
		{
			if ($account_id = $request['account_id'])
			{
				try
				{
					$account = new TBGIncomingEmailAccount($account_id);
					$account->delete();

					return $this->renderJSON(array('message' => $this->getI18n()->__('Incoming email account deleted')));
				}
				catch (Exception $e)
				{
					$this->getResponse()->setHttpStatus(400);
					return $this->renderJSON(array('error' => $this->getI18n()->__('This is not a valid mailing account')));
				}
			}
			else
			{
				$this->getResponse()->setHttpStatus(400);
				return $this->renderJSON(array('error' => $this->getI18n()->__('This is not a valid mailing account')));
			}
		}

		public function runConfigureProjectSettings(TBGRequest $request)
		{
			$this->forward403unless($request->isPost());

			if ($this->access_level != TBGSettings::ACCESS_FULL)
			{
				$project_id = $request['project_id'];

				if ($request['mailing_reply_address'] != '')
				{
					if (filter_var($request['mailing_reply_address'], FILTER_VALIDATE_EMAIL) !== false)
					{
						TBGContext::getModule('mailing')->saveSetting('project_reply_address_'.$project_id, $request->getParameter('mailing_reply_address'));
					}
					else
					{
						$this->getResponse()->setHttpStatus(400);
						return $this->renderJSON(array('message' => TBGContext::getI18n()->__('Please enter a valid email address')));
					}
				}
				elseif ($request->getParameter('mailing_reply_address') == '')
				{
					TBGContext::getModule('mailing')->saveSetting('project_reply_address_'.$project_id, $request->getParameter('mailing_reply_address'));
				}

				return $this->renderJSON(array('failed' => false, 'message' => TBGContext::getI18n()->__('Settings saved')));
			}
			else
			{
				$this->forward403();
			}
		}
	}

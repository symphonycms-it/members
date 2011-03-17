<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Error</h2><p>You cannot directly access this file</p>');

	Class eventMembers_Regenerate_Activation_Code extends Event{

		const ROOTELEMENT = 'members-regenerate-activation-code';

		public static function about(){
			return array(
				'name' => 'Members: Regenerate Activation Code',
				'author' => array(
					'name' => 'Symphony CMS',
					'website' => 'http://symphony-cms.com',
					'email' => 'team@symphony-cms.com'),
				'version' => '1.0',
				'release-date' => '2011-03-12'
			);
		}

		public function load(){
			if(isset($_POST['action'][self::ROOTELEMENT])) return $this->__trigger();
		}

		public static function documentation(){
			return '
				<p>This event will regenerate an activation code for a user and is useful if their current
				activation code has expired. The activation code can be sent to a Member\'s email after
				this event has executed.</p>
				<h3>Example Front-end Form Markup</h3>
				<p>This is an example of the form markup you can use on your front end. An input field
				accepts either the member\'s email address or username.</p>
				<pre class="XML"><code>
				&lt;form method="post"&gt;
					&lt;label&gt;Username: &lt;input name="fields[username]" type="text" value="{$username}"/&gt;&lt;/label&gt;
					or
					&lt;label&gt;Email: &lt;input name="fields[email]" type="text" value="{$email}"/&gt;&lt;/label&gt;
					&lt;input type="submit" name="action['.self::ROOTELEMENT.']" value="Regenerate Activation Code"/&gt;
					&lt;input type="hidden" name="redirect" value="{$root}/"/&gt;
				&lt;/form&gt;
				</code></pre>
				<h3>Example Success XML</h3>
				<pre class="XML"><code>
				&lt;' . self::ROOTELEMENT . ' result="success"&gt;
					&lt;activation-code&gt;{$code}&lt;/activation-code&gt;
				&lt;/' . self::ROOTELEMENT . '&gt;
				</code></pre>
				<h3>Example Error XML</h3>
				<pre class="XML"><code>
				&lt;' . self::ROOTELEMENT . ' result="error"&gt;
					&lt;error&gt;No Activation field found&lt;/error&gt;
					&lt;error&gt;Member not found&lt;/error&gt;
				&lt;/' . self::ROOTELEMENT . '&gt;
				</code></pre>
			';
		}

		protected function __trigger(){
			$result = new XMLElement(self::ROOTELEMENT);
			$fields = $_POST['fields'];

			$activation = extension_Members::$fields['activation'];

			if(!$activation instanceof fieldMemberActivation) {
				$result->setAttribute('result', 'error');
				$result->appendChild(
					new XMLElement('error', __('No Activation field found'))
				);
				return $result;
			}

			// Make sure we dont accidently use an expired code
			$activation->purgeCodes();

			// Check that a member exists first before proceeding.
			$errors = array();
			$identity = SymphonyMember::setIdentityField($fields);
			$member_id = $identity->fetchMemberIDBy($fields, $errors);

			if(is_null($member_id)) {
				$result->setAttribute('result', 'error');
				$result->appendChild(
					new XMLElement('error', __('Member not found.'))
				);
				return $result;
			}

			// Regenerate the code
			$status = Field::__OK__;
			$data = $activation->processRawFieldData(array(
				'activated' => 'no',
			), $status);

			// Update the database setting activation to yes.
			Symphony::Database()->update($data, 'tbl_entries_data_' . $activation->get('id'), ' `entry_id` = ' . $member_id);

			if(isset($_REQUEST['redirect'])) redirect($_REQUEST['redirect']);

			$result->setAttribute('result', 'success');
			$result->appendChild(
				new XMLElement('activation-code', $data['code'])
			);

			return $result;
		}

	}
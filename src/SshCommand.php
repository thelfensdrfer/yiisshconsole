<?php

namespace yiisshconsole;

class LoginFailedException extends \Exception {}
class LoginUnknownException extends \Exception {}
class NotConnectedException extends \Exception {}

class SshCommand extends \CConsoleCommand
{
	/**
	 * Store of the ssh session.
	 *
	 * @var \Net_SSH2
	 */
	private $ssh = null;

	/**
	 * Connect to the ssh server.
	 *
	 * @param string $host
	 * @param array $auth
	 * Login via username/password
	 *     [
	 *         'username' => 'myname',
	 *         'password' => 'mypassword', // can be empty
	 *      ]
	 * or via private key
	 * 		[
	 * 		    'key' => '/path/to/private.key',
	 * 		    'password' => 'mykeypassword', // can be empty
	 * 		]
	 * @param integer $port Default 22
	 * @param integer $timeout Default 10 seconds
	 *
	 * @throws \LoginFailedException If the login failes
	 * @throws \LoginUnknownException If no username is set
	 *
	 * @return bool
	 */
	public function connect($host, $auth, $port = 22, $timeout = 10)
	{
		$this->ssh = new \phpseclib\Net\SSH2($host, $port, $timeout);

		if (!isset($auth['key']) && isset($auth['username'])) {
			// Login via username/password

			$username = $auth['username'];
			$password = isset($auth['password']) ? $auth['password'] : '';

			if (!$this->ssh->login($username, $password))
				throw new LoginFailedException(\Yii::t(
					'YiiSshConsole',
					'Login failed for user {username} using password {answer}!',
					[
						'username' => $username,
						'answer' => !empty($password) ? 1 : 0
					]
				));
			else
				return true;
		} elseif (isset($auth['key']) and isset($auth['username'])) {
			// Login via private key

			$username = $auth['username'];
			$password = isset($auth['key_password']) ? $auth['key_password'] : '';

			$key = new \phpseclib\Crypt\RSA;
			if (!empty($password)) {
				$key->setPassword($password);
			}
			$key->loadKey(file_get_contents($auth['key']));

			if (!$this->ssh->login($username, $key))
				throw new LoginFailedException(\Yii::t(
					'YiiSshConsole',
					'Login failed for user {username} using key with password {answer}!',
					[
						'username' => $username,
						'answer' => !empty($password) ? 1 : 0
					]
				));
			else
				return true;
		} else {
			// No username given

			throw new LoginUnknownException(\Yii::t(
					'YiiSshConsole',
					'No username given!'
			));
		}

		return false;
	}

	/**
	 * Read the next line from the SSH session.
	 *
	 * @return string|null
	 */
	public function readLine()
	{
		$output = $this->ssh->_get_channel_packet(\phpseclib\Net\SSH2::CHANNEL_EXEC);

		return $output === true ? null : $output;
	}

	/**
	 * Run a ssh command for the current connection.
	 *
	 * @param string|array $commands
	 * @param callable $callback
	 *
	 * @throws \NotConnectedException If the client is not connected to the server
	 *
	 * @return string|null
	 */
	public function exec($commands, $callback = null)
	{
		if (!$this->ssh->isConnected())
			throw new NotConnectedException();

		if (is_array($commands))
			$commands = implode(' && ', $commands);

		if ($callback === null)
			$output = '';

		$this->ssh->exec($commands, false);

		while (true) {
			if (is_null($line = $this->readLine())) break;

			if ($callback === null)
				$output .= $line;
			else
				call_user_func($callback, $line, $this);
		}

		if ($callback === null)
			return $output;
		else
			return null;
	}

	/**
	 * Returns the log messages of the connection.
	 *
	 * @return array
	 */
	public function getLog()
	{
		return $this->ssh->getLog();
	}
}

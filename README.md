# Yii SSH Console

Controller with ssh commands for the yii console.

## Example

```php
<?php 

class DeployCommand extends CConsoleCommand
{
	public $defaultAction = 'exec';

	public function actionExec()
	{
		$this->auth('example.com', [
			'username' => 'myusername',
			'password' => 'mypassword', // optional
		]);

		// Or via private key
		/*
		$this->auth('example.com', [
			'username' => 'myusername',
			'key' => '/path/to/private.key',
			'password' => 'mykeypassword', // optional
		]);
		*/

		$output = $this->run('echo "test"');
		echo 'Output: ' . $output; // Output: test

		$output = $this->run([
			'cd /path/to/install',
			'./put_offline.sh',
			'git pull -f',
			'composer install',
			'./yii migrate --interactive=0',
			'./build.sh',
			'./yii cache/flush',
			'./put_online.sh',
		]);

		// Or via callback
		$this->run([
			'cd /path/to/install',
			'./put_offline.sh',
			'git pull -f',
			'composer install',
			'./yii migrate --interactive=0',
			'./build.sh',
			'./yii cache/flush',
			'./put_online.sh',
		], function($line) {
			echo $line;
		});
	}
}
```

And then in the local console:

```
./yiic deploy
```

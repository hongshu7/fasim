<?php
namespace App\Controller;

use Fasim\Core\Controller;
use Fasim\Core\ModelArray;
use Fasim\Facades\Cache;
use App\Model\UserModel;
/**
 * @class MainController
 * 主控制器
 */
class MainController extends Controller {

	public function doDefault() {
		echo 'hello, sir!';
	}

	public function doTest1() {
		$testUser = new UserModel();
		$testUser->nickname = 'test';
		$testUser->gender = 1;

		Cache::getInstance()->set('test_user', $testUser, 3600);

		$testUsers = new ModelArray();
		$testUsers[] = $testUser;
		Cache::getInstance()->set('test_users', $testUsers, 3600);
	}

	public function doTest2() {
		$model = Cache::get('test_user');
		print_r($model);

		$models = Cache::get('test_users');
		print_r($models);
	}

}

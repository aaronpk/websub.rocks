<?php
namespace App;

use Rocks\Http\Request;
use Rocks\Http\Response;
use ORM;
use Config;
use p3k;

class Auth {

  public function start(Request $request) {
    if($request->post('galaxy') != 'vegancheese') {
      return Response::redirect('/');
    }

    // Emailed login links have been removed. Until passkey login is added,
    // signing in only works on installs with authentication bypassed.
    if(!Config::$skipauth) {
      return Response::make(200, page('auth-error', [
        'title' => 'Error - WebSub Rocks!',
        'error' => 'Login Unavailable',
        'error_description' => 'Email login is currently disabled.',
      ]));
    }

    $email = $request->post('email');
    if(!$email) {
      return Response::redirect('/');
    }

    $user = ORM::for_table('users')->where('email', $email)->find_one();

    if(!$user) {
      $user = ORM::for_table('users')->create();
      $user->email = $email;
    }

    $user->auth_code = $code = p3k\random_string(64);
    $user->auth_code_exp = date('Y-m-d H:i:s', time()+60*30);
    $user->save();

    return Response::redirect(Config::$base . 'auth/code?code=' . $code);
  }

  public function code(Request $request) {
    $code = $request->query('code');

    if($code === null) {
      return Response::redirect('/');
    }

    $user = ORM::for_table('users')
      ->where('auth_code', $code)
      ->where_gt('auth_code_exp', date('Y-m-d H:i:s'))
      ->find_one();

    if(!$user) {
      return Response::make(200, page('auth-error', [
        'title' => 'Error - WebSub Rocks!',
        'error' => 'Invalid Link',
        'error_description' => 'The link you followed is invalid or has expired. Please try again.',
      ]));
    }

    $user->auth_code = '';
    $user->auth_code_exp = null;
    $user->last_login = date('Y-m-d H:i:s');
    $user->save();

    p3k\session_setup(true);
    $_SESSION['user_id'] = $user->id;
    $_SESSION['email'] = $user->email;
    $_SESSION['login'] = 'success';
    return Response::redirect('/');
  }

  public function signout(Request $request) {
    p3k\session_setup(true);
    unset($_SESSION['user_id']);
    unset($_SESSION['email']);
    $_SESSION = [];
    session_destroy();
    return Response::redirect('/');
  }

}


<?php
namespace App;

use Rocks\Http\Request;
use Rocks\Http\Response;
use Rocks\Passkeys;
use ORM;
use Config;
use RuntimeException;
use Throwable;
use p3k;

class Auth {

  // Development login for hosts where passkeys can't run, such as plain http
  // on a name other than localhost. Signs in as any email address entered.
  public function start(Request $request) {
    if(!Config::$skipauth) {
      return Response::make(404);
    }

    if($request->post('galaxy') != 'vegancheese') {
      return Response::redirect('/');
    }

    $email = trim((string)$request->post('email'));
    if($email === '') {
      return Response::redirect('/');
    }

    $user = ORM::for_table('users')->where('email', $email)->find_one();

    if(!$user) {
      $user = ORM::for_table('users')->create();
      $user->email = $email;
      $user->date_created = date('Y-m-d H:i:s');
    }

    log_in($user);
    return Response::redirect('/');
  }

  // The passkey endpoints below are called with fetch() and only accept JSON.
  // A cross-site page can't send a JSON body without a CORS preflight, which
  // this site never grants, so that also stands in for a CSRF token here.

  public function register_challenge(Request $request) {
    if($error = self::check_request($request))
      return $error;

    $email = trim((string)($request->json()['email'] ?? ''));

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return self::error('Enter a valid email address.');
    }

    $existing = ORM::for_table('users')
      ->join('passkeys', ['passkeys.user_id', '=', 'users.id'])
      ->where('users.email', $email)
      ->count();
    if($existing) {
      return self::error('There is already an account for that email address. Sign in with your passkey instead.', 409);
    }

    session_setup(true);

    // Always a new account. An older account with this email and no passkey
    // isn't reused, since nothing here proves the person owns the address.
    $webauthn_id = bin2hex(random_bytes(16));

    return Response::json(Passkeys::begin_registration('register', $webauthn_id, $email, [], [
      'email' => $email,
      'webauthn_id' => $webauthn_id,
    ]));
  }

  public function register(Request $request) {
    if($error = self::check_request($request))
      return $error;

    session_setup(true);
    $body = $request->json();

    try {
      $credential = Passkeys::complete_registration('register',
        Passkeys::decode($body['clientDataJSON'] ?? ''),
        Passkeys::decode($body['attestationObject'] ?? ''));
    } catch(RuntimeException $e) {
      return self::error($e->getMessage());
    }

    $db = ORM::get_db();
    $db->beginTransaction();
    try {
      $user = ORM::for_table('users')->create();
      $user->email = $credential['data']['email'];
      $user->webauthn_id = $credential['data']['webauthn_id'];
      $user->date_created = date('Y-m-d H:i:s');
      $user->save();

      Passkeys::save($user->id, $credential);
      $db->commit();
    } catch(Throwable $e) {
      $db->rollBack();
      return self::error('Your account could not be created. Please try again.', 500);
    }

    log_in($user);

    return Response::json(['redirect' => '/']);
  }

  public function login_challenge(Request $request) {
    if($error = self::check_request($request))
      return $error;

    session_setup(true);

    return Response::json(Passkeys::begin_login());
  }

  public function login(Request $request) {
    if($error = self::check_request($request))
      return $error;

    session_setup(true);
    $body = $request->json();

    try {
      $user = Passkeys::complete_login(
        Passkeys::decode($body['id'] ?? ''),
        Passkeys::decode($body['clientDataJSON'] ?? ''),
        Passkeys::decode($body['authenticatorData'] ?? ''),
        Passkeys::decode($body['signature'] ?? ''),
        Passkeys::decode($body['userHandle'] ?? ''));
    } catch(RuntimeException $e) {
      return self::error($e->getMessage());
    }

    log_in($user);

    return Response::json(['redirect' => '/']);
  }

  public function signout(Request $request) {
    session_setup(true);
    unset($_SESSION['user_id']);
    unset($_SESSION['email']);
    $_SESSION = [];
    session_destroy();
    return Response::redirect('/');
  }

  private static function check_request(Request $request) {
    if(!$request->isJson()) {
      return self::error('Expected a JSON request.', 415);
    }
    if(!Passkeys::available()) {
      return self::error('Passkeys need this site to be served over https, or from localhost.');
    }
    return null;
  }

  private static function error($message, $status=400) {
    return Response::json(['error' => $message], $status);
  }

}

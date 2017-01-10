<?php
//Auth::loginUsingId(2);

$api = app('Dingo\Api\Routing\Router');

//$dispatcher = app('Dingo\Api\Dispatcher');

$api->version('v1', function ($api) {

    $api->any('/', function (\Illuminate\Http\Request $request) {
        header('Access-Control-Allow-Origin: *');
        return [$request->method() => 'api.laravel.dev'];
    });

    $api->any('/jsonp', function (\Illuminate\Http\Request $request) {
        return $request->callback . "(" . json_encode([$request->method() => 'api.qljiang.com']) . ")";
    });

    $api->group(['namespace' => 'App\Api\Controllers'], function ($api) {

        $api->post('auth/login', 'AuthController@authenticate');
        $api->post('auth/register', 'AuthController@register');

        $api->resource('lessons', 'LessonController');

        $api->group(['middleware' => 'jwt.auth'], function ($api) {
            $api->get('auth/me', 'AuthController@getAuthenticatedUser');
        });
    });
});

//Set up a route to respond to the incoming auth code requests
Route::get('oauth/authorize', [
    'as'         => 'oauth.authorize.get',
    'middleware' => ['check-authorization-params', 'auth'], function () {
        $authParams = Authorizer::getAuthCodeRequestParams();

        $formParams = array_except($authParams, 'client');

        $formParams['client_id'] = $authParams['client']->getId();

        $formParams['scope'] = implode(config('oauth2.scope_delimiter'), array_map(function ($scope) {
            return $scope->getId();
        }, $authParams['scopes']));

        return view('oauth.authorization-form', ['params' => $formParams, 'client' => $authParams['client']]);
    },
]);

//Set up a route to respond to the form being posted.
Route::post('oauth/authorize', [
    'as'         => 'oauth.authorize.post',
    'middleware' => ['csrf', 'check-authorization-params', 'auth'], function () {

        $params = Authorizer::getAuthCodeRequestParams();
        $params['user_id'] = Auth::user()->id;
        $redirectUri = '/';

        // If the user has allowed the client to access its data, redirect back to the client with an auth code.
        if (Request::has('approve')) {
            $redirectUri = Authorizer::issueAuthCode('user', $params['user_id'], $params);
        }

        // If the user has denied the client to access its data, redirect back to the client with an error message.
        if (Request::has('deny')) {
            $redirectUri = Authorizer::authCodeRequestDeniedRedirectUri();
        }

        return Redirect::to($redirectUri);
    }]);

//Add a route to respond to the access token requests
Route::post('oauth/access_token', function () {
    return Response::json(Authorizer::issueAccessToken());
});
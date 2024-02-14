<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'integration'], function ($router) {

    $router->get('google/auth/url', 'IntegrationController@getAuthUrl');
    $router->post('google/auth/login', 'IntegrationController@loginGoogle');

    $router->group(['middleware' => ['auth:api'], 'prefix' => 'calendar'],
        function () use ($router) {

            $router->get('listAll/{email}','IntegrationController@listAll');
            $router->get('listEvents/{calendarId}','IntegrationController@events');
            $router->get('groupByCalendar', 'IntegrationController@groupByCalendar');
        }
    );

});

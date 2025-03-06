<?php

declare(strict_types=1);

use Slim\App;
use App\Helpers\JwtHelper;
use App\Middleware\JwtMiddleware;
use App\Controllers\ParkingController;
use App\Controllers\BookingController;
use App\Controllers\Auth\AuthController;
use Slim\Exception\HttpNotFoundException;
use App\Application\Actions\User\ViewUserAction;
use App\Application\Actions\User\ListUsersAction;
use App\Controllers\HomeController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {

    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });
    $authMiddleware = function (Request $request, Response $response, $next) {
        $header = $request->getHeaderLine('Authorization');
        if (!$header) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $token = str_replace('Bearer ', '', $header);
        $decoded = JwtHelper::verifyToken($token);

        if (!$decoded) {
            $response->getBody()->write(json_encode(['error' => 'Invalid Token']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        return $next($request, $response);
    };
    $app->group('/analytics', function (Group $group) {
        $group->get('', [HomeController::class, 'analytics']);
    })->add(JwtMiddleware::class);
    $app->group('/auth', function (Group $group) {

        $group->post('/register', [AuthController::class, 'register']);
        $group->post('/login', [AuthController::class, 'login']);
    });

    $app->group('/parking', function (Group $group) {

        $group->get('', [ParkingController::class, 'getParkings']);
        $group->post('', [ParkingController::class, 'addParking']);
        $group->get('/{id}', [ParkingController::class, 'getParkingById']);
        $group->put('/{id}', [ParkingController::class, 'updateParking']);
        $group->delete('/{id}', [ParkingController::class, 'deleteParking']);
        $group->get('/{id}/slots', [ParkingController::class, 'getSlotsByParkingId']);
    })->add(JwtMiddleware::class);
    $app->group('/booking', function (Group $group) {
        $group->get('', [BookingController::class, 'getBookings']);
        $group->post('', [BookingController::class, 'addBooking']);
        $group->put('/{id}/cancel', [BookingController::class, 'cancelBooking']);
        $group->post('/available-slots', [BookingController::class, 'getAvailableSlots']);
    })->add(JwtMiddleware::class);
    /**
     * Catch-all route to serve a 404 Not Found page if none of the routes match
     * NOTE: make sure this route is defined last
     */
    $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
        throw new HttpNotFoundException($request);
    });
};

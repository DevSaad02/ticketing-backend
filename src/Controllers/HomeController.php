<?php

namespace App\Controllers;

use Model;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpInternalServerErrorException;
use Psr\Http\Message\ServerRequestInterface as Request;

class HomeController
{
    public function response($response, $data, $status = 200)
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    public function analytics(Request $request, Response $response)
    {
        try {
            // Fetch total parkings
            $totalParkings = Model::factory('Parking')->count();

            // Fetch total slots
            $totalSlots = Model::factory('Slot')->count();

            // Fetch total users with role_id 2
            $totalUsers = Model::factory('User')->where('role_id', 2)->count();

            // Prepare the data
            $data = [
                'total_parkings' => $totalParkings,
                'total_slots' => $totalSlots,
                'total_users' => $totalUsers
            ];

            // Return the response
            return $this->response($response, $data);
        } catch (PDOException $e) {
            throw new HttpInternalServerErrorException($request, "Database error occurred: " . $e->getMessage());
        } catch (\Exception $e) {
            throw new HttpInternalServerErrorException($request, "An error occurred: " . $e->getMessage());
        }
    }
}
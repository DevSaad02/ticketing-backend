<?php

namespace App\Controllers;

use Model;
use PDOException;
use App\Helpers\JwtHelper;
use Psr\Log\LoggerInterface;
use App\Services\ArrayConversionService;
use Slim\Exception\HttpBadRequestException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpInternalServerErrorException;
use Psr\Http\Message\ServerRequestInterface as Request;

class ParkingController extends HomeController
{
    protected $logger;
    private $arrayConversionService;
    // return $this->response($response, [
    //     'status' => 'success',
    //     'message' => 'working'
    // ]);
    public function __construct(LoggerInterface $logger, ArrayConversionService $arrayConversionService)
    {
        $this->logger = $logger;
        $this->arrayConversionService = $arrayConversionService;
    }

    public function getParkings(Request $request, Response $response)
    {
        $this->logger->info('Fetching parkings');
        try {
            $parkings = Model::factory('Parking')->find_many();
            $this->logger->info('Parkings fetched successfully');
            return $this->response($response, [
                'status' => 'success',
                'message' => 'Parkings fetched successfully',
                'data' => $this->arrayConversionService->convertCollectionToArray($parkings)
            ]);
        } catch (PDOException $e) {
            return $this->response($response, [
                'status' => 'error',
                'message' => 'Error fetching parkings',
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch parkings", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "An error occurred: " . $e->getMessage());
        }
    }

    public function addParking(Request $request, Response $response)
    {
        $this->logger->info('Adding parking');
        try {
            $data = $request->getParsedBody();
            if (empty($data['place']) || empty($data['vehicleType']) || empty($data['landmark']) || empty($data['address'])) {
                throw new HttpBadRequestException($request, "All fields are required");
            }
            // Check if parking already exists
            $parking = Model::factory('Parking')->where('address', $data['address'])->find_one();
            if($parking) {
                throw new HttpBadRequestException($request, "Parking already exists");
            }
            // Create parking
            $parking = Model::factory('Parking')->create();
            $parking->place = $data['place'];
            $parking->vehicle_type = $data['vehicleType'];
            $parking->landmark = $data['landmark'];
            $parking->address = $data['address'];
            $parking->save();
            // Create slots
            $numSlots = (int)$data['slots'];
            for($i = 0; $i < $numSlots; $i++) {
                $slot = Model::factory('Slot')->create();
                $slot->system_id = $i + ($parking->id * 1000);
                $slot->parking_id = $parking->id;
                $slot->status = 'available';
                $slot->save();
            }
            return $this->response($response, [
                'status' => 'success',
                'message' => 'Parking added successfully',
                'data' => $parking,
                'slots' => 'Slots added: '. $numSlots
            ], 201);
        } catch (PDOException $e) {
            $this->logger->error("Database error occurred while adding parking", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "Database error occurred: " . $e->getMessage());
        } catch (HttpBadRequestException $e) {
            $this->logger->warning("Bad request: " . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error("Failed to add parking", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "An error occurred: " . $e->getMessage());
        }
    }

    public function getParkingById(Request $request, Response $response, $id)
    {
        if (is_array($id)) {
            $id = reset($id);
            $id = (int) $id;
        }
        $this->logger->info('Fetching parking by id', ['id' => $id]);
        try {
            $parking = Model::factory('Parking')->find_one($id);
            if(!$parking) {
                throw new HttpBadRequestException($request, "Parking not found");
            }
            // Slots count
            $parking->slots = Model::factory('Slot')->where('parking_id', $id)->count();
            return $this->response($response, [
                'status' => 'success',
                'message' => 'Parking fetched successfully',
                'data' => $this->arrayConversionService->convertToArray($parking)
            ]);
        } catch (PDOException $e) {
            $this->logger->error("Database error occurred while fetching parking by id", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "Database error occurred: " . $e->getMessage());
        } catch (HttpBadRequestException $e) {
            $this->logger->warning("Bad request: " . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch parking by id", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "An error occurred: " . $e->getMessage());
        }
    }

    public function updateParking(Request $request, Response $response, $id)
    {
        if (is_array($id)) {
            $id = reset($id);
            $id = (int) $id;
        }
        $this->logger->info('Updating parking', ['id' => $id]);
        try {
            $data = $request->getParsedBody();
            if(empty($data['place']) || empty($data['vehicleType']) || empty($data['landmark']) || empty($data['address'])) {
                throw new HttpBadRequestException($request, "All fields are required");
            }
            $parking = Model::factory('Parking')->find_one($id);
            if(!$parking) {
                throw new HttpBadRequestException($request, "Parking not found");
            }
            $parking->place = $data['place'];
            $parking->vehicle_type = $data['vehicleType'];
            $parking->landmark = $data['landmark'];
            $parking->address = $data['address'];
            $parking->save();
            $this->logger->info('Parking updated successfully');
            return $this->response($response, [
                'status' => 'success',
                'message' => 'Parking updated successfully',
                'data' => $parking
            ]);
        } catch (PDOException $e) {
            $this->logger->error("Database error occurred while updating parking", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "Database error occurred: " . $e->getMessage());
        } catch (HttpBadRequestException $e) {
            $this->logger->warning("Bad request: " . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error("Failed to update parking", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "An error occurred: " . $e->getMessage());
        }
    }
    public function deleteParking(Request $request, Response $response, $id)
    {
        if (is_array($id)) {
            $id = reset($id);
            $id = (int) $id;
        }
        $this->logger->info('Deleting parking', ['id' => $id]);
        try {
            $parking = Model::factory('Parking')->find_one($id);
            if(!$parking) {
                throw new HttpBadRequestException($request, "Parking not found");
            }
            $this->logger->info('Deleting Parking Slots');
             // Delete slots
             $slots = Model::factory('Slot')->where('parking_id', $id)->find_many();
             if($slots) {
                 foreach($slots as $slot) {
                     $slot->delete();
                 }
             }
            $this->logger->info('Deleting Parking');
            // Delete parking
            $parking->delete();
           
            return $this->response($response, [
                'status' => 'success',
                'message' => 'Parking deleted successfully',
                'slots' => 'Slots deleted: '. count($slots)
            ]);
        } catch (PDOException $e) {
            $this->logger->error("Database error occurred while deleting parking", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "Database error occurred: " . $e->getMessage());
        } catch (HttpBadRequestException $e) {
            $this->logger->warning("Bad request: " . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error("Failed to delete parking", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "An error occurred: " . $e->getMessage());
        }
    }
    public function getSlotsByParkingId(Request $request, Response $response, $id)
    {
        if (is_array($id)) {
            $id = reset($id);
            $id = (int) $id;
        }
        $this->logger->info('Fetching slots by parking id', ['id' => $id]);
        try {
            $slots = Model::factory('Slot')->where('parking_id', $id)->find_many();
            return $this->response($response, [
                'status' => 'success',
                'message' => 'Slots fetched successfully',
                'data' => $this->arrayConversionService->convertCollectionToArray($slots)
            ]);
        } catch (PDOException $e) {
            $this->logger->error("Database error occurred while fetching slots by parking id", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "Database error occurred: " . $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch slots by parking id", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "An error occurred: " . $e->getMessage());
        }
    }
}

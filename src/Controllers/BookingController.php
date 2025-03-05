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
use ORM;

class BookingController extends HomeController
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

    public function addBooking(Request $request, Response $response)
    {
        $this->logger->info('Adding parking');
        try {
            $data = $request->getParsedBody();
            if (empty($data['vehicle_number']) || empty($data['contact_number']) || empty($data['date']) || empty($data['start_time']) || empty($data['end_time'])) {
                throw new HttpBadRequestException($request, "Fields are required");
            }
            // Check if booking already exists
            $booking = Model::factory('Booking')->where('date', $data['date'])->where('start_time', $data['start_time'])->where('end_time', $data['end_time'])->find_one();
            if($booking){
                throw new HttpBadRequestException($request, "Booking already exists");
            }
            // Create Booking
            $booking = Model::factory('Booking')->create();
            $booking->user_id = $data['user_id'];
            $booking->slot_id = $data['slot_id'];
            $booking->vehicle_registration_number = $data['vehicle_registration_number'];
            $booking->vehicle_number = $data['vehicle_number'];
            $booking->vehicle_type = $data['vehicle_type'];
            $booking->vehicle_name = $data['vehicle_name'];
            $booking->vehicle_owner = $data['vehicle_owner'];
            $booking->contact_number = $data['contact_number'];
            $booking->date = $data['date'];
            $booking->start_time = $data['start_time'];
            $booking->end_time = $data['end_time'];
            $booking->save();
            $this->logger->info('Booking added successfully');
            //Create Booked Slots
            $booked_slot = Model::factory('BookedSlot')->create();
            $booked_slot->slot_id = $data['slot_id'];
            $booked_slot->date = $data['date'];
            $booked_slot->start_time = $data['start_time'];
            $booked_slot->end_time = $data['end_time'];
            $booked_slot->save();
            return $this->response($response, [
                'status' => 'success',
                'message' => 'Booking added successfully', 
                'data' => $booking], 201);
        } catch (PDOException $e) {
            $this->logger->error("Database error occurred while adding booking", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "Database error occurred: " . $e->getMessage());
        } catch (HttpBadRequestException $e) {
            $this->logger->warning("Bad request: " . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error("Failed to add booking", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "An error occurred: " . $e->getMessage());
        }
    }

    public function getAvailableSlots(Request $request, Response $response)
    {
        $this->logger->info('Fetching available slots');
        try{
            $data = $request->getParsedBody();
            if (empty($data['parking_id']) || empty($data['date']) || empty($data['start_time']) || empty($data['end_time'])) {
                throw new HttpBadRequestException($request, "Fields are required");
            }
            $parkingId = is_array($data['parking_id']) ? (int) reset($data['parking_id']) : (int) $data['parking_id'];
            $date = (string) $data['date'];
            $startTime = (string) $data['start_time'];
            $endTime = (string) $data['end_time'];

            ORM::configure('logging', true);
    


        
            $slots = Model::factory('Slot')
            ->table_alias('s')
            ->left_outer_join('booked_slot', 's.id = booked_slot.slot_id AND booked_slot.date = '.$date)
            ->where('s.parking_id', $parkingId)
            ->where_raw('(booked_slot.id IS NULL OR NOT (booked_slot.start_time < '.$endTime.' AND booked_slot.end_time > '.$startTime.'))')
            ->select_expr('s.*, CASE 
                WHEN booked_slot.id IS NOT NULL 
                AND booked_slot.start_time < '.$endTime.' 
                AND booked_slot.end_time > '.$startTime.'
                THEN "reserved" ELSE "available" 
                END AS status');

// Log the query and parameters before executing
$lastQuery = $slots->_build_select();
$this->logger->info("Query to be executed: " . $lastQuery);
$slots = $slots->find_many();

                
            return $this->response($response, [
                'status' => 'success',
                'message' => 'Available slots fetched successfully',
                'data' => $this->arrayConversionService->convertCollectionToArray($slots)
            ]);
        } catch (PDOException $e) {
            $lastQuery = ORM::get_last_query();
                $this->logger->info("Last executed query: " . $lastQuery);
            // $this->logger->error("Database error occurred while fetching available slots", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "Database error occurred: " . $e->getMessage());
        } catch (HttpBadRequestException $e) {
            $this->logger->warning("Bad request: " . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch available slots", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "An error occurred: " . $e->getMessage());
        }
    }

}

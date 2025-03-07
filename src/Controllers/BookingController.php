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
    
    public function __construct(LoggerInterface $logger, ArrayConversionService $arrayConversionService)
    {
        $this->logger = $logger;
        $this->arrayConversionService = $arrayConversionService;
    }

    public function getBookings(Request $request, Response $response)
    {
        $this->logger->info('Fetching bookings');
        try {
            $user_id = $request->getAttribute('user_id');
            $user = Model::factory('User')->where('id', $user_id)->find_one();
            if ($user->role_id == "2") {
                $bookings = Model::factory('Booking')->where('user_id', $user_id)->order_by_desc('id')->find_many();
            } else {
                $bookings = Model::factory('Booking')->order_by_desc('id')->find_many();
            }
            $this->logger->info('Bookings fetched successfully');
            return $this->response($response, [
                'status' => 'success',
                'message' => 'Bookings fetched successfully',
                'data' => $this->arrayConversionService->convertCollectionToArray($bookings)
            ]);
        } catch (PDOException $e) {
            return $this->response($response, [
                'status' => 'error',
                'message' => 'Error fetching bookings',
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch bookings", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "An error occurred: " . $e->getMessage());
        }
    }

    public function addBooking(Request $request, Response $response)
    {
        $this->logger->info('Adding parking');
        try {
            $data = $request->getParsedBody();
            $data['user_id'] = $request->getAttribute('user_id');
            if (empty($data['vehicle_registration_number']) || empty($data['contact_number']) || empty($data['date']) || empty($data['start_time']) || empty($data['end_time'])) {
                throw new HttpBadRequestException($request, "Fields are required");
            }
            // Check if booking already exists
            $booking = Model::factory('Booking')->where('date', $data['date'])->where('start_time', $data['start_time'])->where('end_time', $data['end_time'])->find_one();
            if ($booking) {
                throw new HttpBadRequestException($request, "Booking already exists");
            }
            // Create Booking
            $booking = Model::factory('Booking')->create();
            $booking->user_id = $data['user_id'];
            $booking->slot_id = $data['slot_id'];
            $booking->vehicle_registration_number = $data['vehicle_registration_number'];
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
                'data' => $this->arrayConversionService->convertToArray($booking)
            ], 201);
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
        try {
            $data = $request->getParsedBody();
            if (empty($data['parking_id']) || empty($data['date']) || empty($data['start_time']) || empty($data['end_time'])) {
                throw new HttpBadRequestException($request, "Fields are required");
            }

            $parkingId = is_array($data['parking_id']) ? (int) reset($data['parking_id']) : (int) $data['parking_id'];
            $date = (string) $data['date'];
            $startTime = (string) $data['start_time'];
            $endTime = (string) $data['end_time'];

            // Validate that end_time is greater than start_time
            if ($startTime >= $endTime) {
                throw new HttpBadRequestException($request, "End time must be greater than start time");
            }

            ORM::configure('logging', true);

            $slots = Model::factory('Slot')
                ->table_alias('s')
                ->left_outer_join('booked_slot', 's.id = booked_slot.slot_id AND booked_slot.date = "' . $date . '"')
                ->where_raw('s.parking_id = "' . $parkingId . '"')
                ->where_raw('(booked_slot.id IS NULL OR NOT (booked_slot.start_time < "' . $endTime . '" AND booked_slot.end_time > "' . $startTime . '"))')
                ->select_expr('s.*, CASE 
                WHEN booked_slot.id IS NOT NULL 
                AND booked_slot.start_time < "' . $endTime . '" 
                AND booked_slot.end_time > "' . $startTime . '"
                THEN "occupied" ELSE "available" 
                END AS slot_status');

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
            $this->logger->error("Database error occurred while fetching available slots", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "Database error occurred: " . $e->getMessage());
        } catch (HttpBadRequestException $e) {
            $this->logger->warning("Bad request: " . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch available slots", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "An error occurred: " . $e->getMessage());
        }
    }

    public function cancelBooking(Request $request, Response $response, $id)
    {
        if (is_array($id)) {
            $id = reset($id);
            $id = (int) $id;
        }
        $this->logger->info('Cancel Booking');
        try {
            $booking = Model::factory('Booking')->where('id',$id)->find_one();
            $this->logger->info('Booking fetched successfully');
            //Update booking status
            $booking->status = "canceled";
            $booking->save();
            //Delete record from booked slot
            $booked_slot = Model::factory('BookedSlot')
            ->where('date',$booking->date)
            ->where('start_time',$booking->start_time)
            ->where('end_time',$booking->end_time)->find_one();
            $booked_slot->delete();
            return $this->response($response, [
                'status' => 'success',
                'message' => 'Booking status updated successfully',
                'booking' => $this->arrayConversionService->convertToArray($booking),
                'booked_slot' => $this->arrayConversionService->convertToArray($booked_slot)
            ]);
        } catch (PDOException $e) {
            return $this->response($response, [
                'status' => 'error',
                'message' => 'Error updating booking status',
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to update booking status", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "An error occurred: " . $e->getMessage());
        }
    }

}
